<?php

namespace OCA\SocialLogin\Service;

use DateTime;
use OCA\SocialLogin\Db\Tokens;
use OCA\SocialLogin\Db\TokensMapper;
use Psr\Log\LoggerInterface;

class TokenService
{
    private $tokensMapper;
    private $configService;
    private $logger;
    private $adapter;
    private $adapterService;


    public function __construct(TokensMapper $tokensMapper, LoggerInterface $logger, ConfigService $configService, AdapterService $adapterService)
    {
        $this->tokensMapper = $tokensMapper;
        $this->logger = $logger;
        $this->configService = $configService;
        $this->adapterService = $adapterService;
    }

    public function authenticate($adapter, $providerType, $providerId){
        $adapter->authenticate();

        $profile = $adapter->getUserProfile(); // TODO whole paragraph: refactor to service / trait
        $profileId = preg_replace('#.*/#', '', rtrim($profile->identifier, '/'));
        $uid = $providerId.'-'.$profileId;
        if (strlen($uid) > 64 || !preg_match('#^[a-z0-9_.@-]+$#i', $profileId)) {
            $uid = $providerId.'-'.md5($profileId);
        }

        $accessTokens = $adapter->getAccessToken();
        $this->saveTokens($accessTokens, $uid, $providerType, $providerId);
    }

    /**
     * @throws \OC\User\LoginException
     * @throws \OCP\DB\Exception
     */
    public function refreshTokens() : void
    {
        if ($this->tokensMapper->findAll() == null) {
            $this->logger->info("No tokens in database.");
        } else {
            $allTokens = $this->tokensMapper->findAll();
        }


        foreach ($allTokens as $tokens) {
            if ($this->hasAccessTokenExpired($tokens)) {
                $config = $this->configService->customConfig($tokens->getProviderType(), $tokens->getProviderId());
                if (!array_key_exists('saveTokens', $config) || $config['saveTokens'] != true) {
                    $this->tokensMapper->delete($tokens); // Delete keys from formerly active instances.
                    $this->logger->warning("Deleted old key by {uid}.", array('uid' => $tokens->getUid()));
                    continue;
                }
                $this->adapter = $this->adapterService->new(ConfigService::TYPE_CLASSES[$tokens->getProviderType()],
                    $config, null);
                $this->logger->info("Trying to refresh token for {uid}.", array('uid' => $tokens->getUid()));
                $parameters = array(
                    'client_id' => $config['keys']['id'],
                    'client_secret' => $config['keys']['secret'],
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $tokens->getRefreshToken(),
                    'scope' => $config['scope']
                );
                $response = $this->adapter->refreshAccessToken($parameters);#
                $responseArr = json_decode($response, true);

                $this->logger->info("Saving refreshed token for {uid}.", array('uid' => $tokens->getUid()));
                $this->saveTokens($responseArr, $tokens->getUid(), $tokens->getProviderType(), $tokens->getProviderId());
            } else {
                $this->logger->info("Token for {uid} has not yet expired.", array('uid' => $tokens->getUid()));
            }
        }
    }


    /**
     * @throws \OCP\DB\Exception
     */
    public function saveTokens(array $accessTokens, string $uid, string $providerType, string $providerId): void
    {
        if (!array_key_exists('expires_at', $accessTokens) && array_key_exists('expires_in', $accessTokens)) {
            $accessTokens['expires_at'] = time() + $accessTokens['expires_in'];
        }
        $tokens = new Tokens();
        $tokens->setUid($uid);
        $tokens->setAccessToken($accessTokens['access_token']);
        $tokens->setRefreshToken($accessTokens['refresh_token']);
        $tokens->setExpiresAt(new DateTime('@'.$accessTokens['expires_at']));
        $tokens->setProviderType($providerType);
        $tokens->setProviderId($providerId);
        $this->tokensMapper->insertOrUpdate($tokens);
    }

    protected function hasAccessTokenExpired(Tokens $tokens): bool
    {
        return $tokens->getExpiresAt() < new DateTime('@'.time());
    }
}