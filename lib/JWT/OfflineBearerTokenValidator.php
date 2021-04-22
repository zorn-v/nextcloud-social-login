<?php

namespace OCA\SocialLogin\JWT;

use \OCA\SocialLogin\Db\PublicKey;
use \OCA\SocialLogin\Db\PublicKeyMapper;

use OCP\ILogger;
use OCP\IConfig;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

use Lcobucci\Clock\SystemClock;

use Firebase\JWT\JWK;

class OfflineBearerTokenValidator {

    // Keycloak uses a default of 86400 seconds (1 day) as caching time for public keys
	// https://www.keycloak.org/docs/latest/securing_apps/index.html#_java_adapter_config
	const PUBLIC_KEY_CACHING_TIME = 86400;

	// Avoid DoSing Issuer by issuing too many requests triggered by an attacker with bad kids
	// Keycloak uses a default of 10 seconds as a minimum time between JWKS requests
	// https://www.keycloak.org/docs/latest/securing_apps/index.html#_java_adapter_config
	const MIN_TIME_BETWEEN_JWKS_REQUESTS = 10;
	const LAST_JWKS_REQUEST_TIME_KEY = 'last_jwks_request_time';

	/** @var PublicKeyMapper */
	private $publicKeyMapper;
	/** @var ILogger */
	private $logger;
	/** @var IConfig */
	private $config;
	/** @var string */
	private string $appName;

    public function __construct(PublicKeyMapper $publicKeyMapper,
                                string $appName,
                                IConfig $config) {
		$this->publicKeyMapper = $publicKeyMapper;
		$this->appName = $appName;
        $this->config = $config;

		$this->context = ["app" => $this->appName];
        $this->logger = \OC::$server->getLogger();
    }

	/**
	 * Validates that the given $token is
	 *   - issued by $issuer
	 *   - issued for $clientId
	 *   - still valid
	 *   - signed with the signing keys received from $jwks_uri
	 * 
	 * @param Token $token The parsed JWT
	 * @param string $issuer The issuer of the JWT
	 * @param string $clientId The OAuth clientId
	 * @param string $jwks_uri The URI to get the JWKs from 
	 */
    public function validate(Token $token, string $issuer, string $clientId, string $jwks_uri) {
		$keyId = $token->headers()->get('kid');
        $signingKey = $this->getSigningKey($keyId, $issuer, $jwks_uri);
        if(is_null($signingKey)) {
            return false;
        }
        // https://lcobucci-jwt.readthedocs.io/en/latest/validating-tokens/
		$constraints = [
			new IssuedBy($issuer),
			new StrictValidIssuedAndExpiration(SystemClock::fromUTC()),
			new PermittedFor($clientId),
			new SignedWith(
				new Signer\Rsa\Sha256(),
				Signer\Key\InMemory::plainText($signingKey->pem),
			),
		];
		$validator = new Validator();
		try {
			$validator->assert($token, ...$constraints);
			return true;
		} catch (RequiredConstraintsViolated $e) {
			// list of constraints violation exceptions:
			$this->logger->warning("Token validation failed with {$e}", $this->context);
		}
		return false;
    }

    /**
	 * Get the signing key for a bearer token. Returns null if none could be found.
	 * 
	 * @param String $bearerToken Bearer token to get the signing key for
	 * @return PublicKey|null
	 */
	private function getSigningKey(string $keyId, string $issuer, string $jwks_uri) {
        $publicKey = $this->publicKeyMapper->find($keyId, $issuer);

		// Key couldn't be found, fetch new ones
		if(is_null($publicKey)) {
			$this->logger->debug("Signing key not found, will fetch new ones.", $this->context);
			if(!$this->updateSigningKeys($issuer, $jwks_uri)) {
				return null;
			}
			$publicKey = $this->publicKeyMapper->find($keyId, $issuer);
		} else {
			$this->logger->debug("Signing key found, will check cache.", $this->context);
			// Update cache if necessary
			$keyAge = time() - $publicKey->lastUpdated;
			if($keyAge >= self::PUBLIC_KEY_CACHING_TIME) {
				$this->logger->debug("Cache is outdated, will update.", $this->context);
				if(!$this->updateSigningKeys($issuer)) {
					return null;
				}
				$publicKey = $this->publicKeyMapper->find($keyId, $issuer);
			}
		}

		return $publicKey;
	}

	/**
	 * Updates the signing keys from issuer. Replaces the old keys.
	 * 
	 * @param String $issuer Issuer to fetch the keys from
	 * @return Array|null 
	 */
	private function updateSigningKeys(string $issuer, string $jwks_uri) {
		$lastFetchedStr = $this->config->getAppValue($this->appName, self::LAST_JWKS_REQUEST_TIME_KEY, '0');
		$lastFetched = (int)$lastFetchedStr;
		$this->logger->debug("Last jwks update at {$lastFetchedStr}", $this->context);

		if(time() - $lastFetched < self::MIN_TIME_BETWEEN_JWKS_REQUESTS) {
			$this->logger->warning("Too many update signing key requests", $this->context);
			return false;
		}

		$this->logger->debug("Fetching new signing keys for {$issuer}.", $this->context);

		$jwks = $this->fetchSigningKeys($issuer, $jwks_uri);

		if(is_null($jwks)) {
			$this->logger->warning("Couldn't fetch keys for issuer {$issuer}", $this->context);
			return false;
		}

		try {
			$publicKeys = JWK::parseKeySet($jwks);
		} catch(\UnexpectedValueException $e) {
			$this->logger->warning("Error when parsing JWKs for issuer {$issuer}. {$e}", $this->context);
			return false;
		}

		$updateTime = time();
		// Remove outdated public keys prior inserting new ones
		$expiredDate = $updateTime - self::PUBLIC_KEY_CACHING_TIME;
		$this->publicKeyMapper->deleteAllOlderThan($issuer, $expiredDate);

		foreach($publicKeys as $kid => $pem) {
			$key = \openssl_pkey_get_details($pem)['key'];
			// In case openssl_pkey_get_details returned an error
			if($key == false) {
				continue;
			}
			$this->logger->debug("Adding public key {$kid}: {$key}", $this->context);
			$k = new PublicKey();
			$k->setKid($kid);
			$k->setIssuer($issuer);
			$k->setPem($key);
			$k->setLastUpdated($updateTime);
			$this->publicKeyMapper->insertOrUpdate($k);
		}
		$this->config->setAppValue($this->appName, self::LAST_JWKS_REQUEST_TIME_KEY, strval(time()));
		return true;
	}

	/**
	 * Fetches new signing keys for issuer.
	 * 
	 * @param String $issuer Issuer to fetch the keys from
	 * @return mixed If JSON can be decoded, returns associative array. Otherwise null.
	 */
	private function fetchSigningKeys(string $issuer, string $jwks_uri) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $jwks_uri);
		$result = curl_exec($ch);
		curl_close($ch);

		return json_decode($result, true);
	}
}