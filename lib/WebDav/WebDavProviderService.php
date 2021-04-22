<?php

namespace OCA\SocialLogin\WebDav;

use OCA\SocialLogin\Service\ProviderService;
use OCA\SocialLogin\JWT\OfflineBearerTokenValidator;
use OCA\SocialLogin\Db\PublicKeyMapper;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Encoding\JoseEncoder;

class WebDavProviderService extends ProviderService
{
    const BEARER_TOKEN = 'bearer-token';
    const CLAIMS = 'claims';

	/**
	 * Handles the WebDav login using an OAuth2 bearer token.
	 * 
	 * @param string $bearerToken OAuth2 bearer token of the user
	 */
    public function handleWebDav(string $bearerToken) {
        $token = $this->getToken($bearerToken);
		if(is_null($token)) {
			return;
		}
		$issuer = $token->claims()->get('iss');
        if(is_null($issuer)) {
			return;
		}
        $provider = $this->findProviderByIssuer($issuer);
        if(is_null($provider) || !isset($provider['webdavEnabled']) || $provider['webdavEnabled'] !== 'on') {
			return;
		}
        $config = $this->generateCustomProviderConfig(ProviderService::TYPE_OIDC, $provider);
        
        if(!$this->validateToken($token, $issuer, $provider)) {
            return;
        }
        
        // Store token for adapter
        $this->storage->set(self::BEARER_TOKEN, $bearerToken);
        $this->storage->set(self::CLAIMS, $token->claims()->toString());
        return $this->auth(CustomWebDavAdapter::class, $config, $provider['name'], 'WebDav');
    }

    /**
	 * Tries parsing a bearer token using the JoseEncoder.
	 * 
	 * @param string $bearerToken Bearer token as string
	 * @return Token|null Parsed token if successful, null otherwise
	 */
	private function getToken(string $bearerToken) {
		$parser = new Parser(new JoseEncoder());
		try {
			return $parser->parse($bearerToken);
		} catch(\Lcobucci\JWT\Exception $e) {
			\OC::$server->getLogger()->debug("Failed parsing token from {$bearerToken}", ["app" => $this->appName]);
		}
		return null;
	}

	// TODO: Find providers other than custom oidc
    private function findProviderByIssuer(string $issuer) {
		$providers = $this->getCustomProviders();
		if(!isset($providers[ProviderService::TYPE_OIDC])) {
			return null;
		}
		foreach ($providers[ProviderService::TYPE_OIDC] as $prov) {
			$str = implode(",", $prov);
			\OC::$server->getLogger()->debug("Checking provider {$str}", ["app" => $this->appName]);
			if(isset($prov['issuer']) && $prov['issuer'] === $issuer) {
                return $prov;
			}
		}
		return null;
	}

    private function validateToken($token, $issuer, $provider) {
        $jwks_uri = $provider['jwks_uri'];
        if(is_null($jwks_uri)) {
            return $this->onlineValidation($token, $provider);
        }
        return $this->offlineValidation($token, $issuer, $provider);
    }

    private function onlineValidation($token, $provider) {
        // TODO: Try to make call to token validation endpoint for providers not supporting JWKs
        return false;
    }

    private function offlineValidation($token, $issuer, $provider) {
		$publicKeyMapper = new PublicKeyMapper(\OC::$server->getDatabaseConnection());
		$validator = new OfflineBearerTokenValidator(
			$publicKeyMapper,
			$this->appName,
			$this->config
		);
		return $validator->validate($token, $issuer, $provider['clientId'], $provider['jwks_uri']);
    }
}
