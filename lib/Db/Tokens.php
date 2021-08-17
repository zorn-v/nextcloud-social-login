<?php

namespace OCA\SocialLogin\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;

class Tokens extends Entity
{
    /** @var string Nextcloud user id */
    protected $uid;
    /** @var string */
    protected $accessToken;
    /** @var string */
    protected $refreshToken;
    /** @var DateTime */
    protected $expiresAt;
    /** @var string */
    protected $providerType;
    /** @var string */
    protected $providerId;

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     */
    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return DateTime
     */
    public function getExpiresAt(): DateTime  // TODO datetime, string, int?
    {
        return $this->expiresAt;
    }

    /**
     * @param DateTime $expiresAt (UNIX timestamp)
     */
    public function setExpiresAt($expiresAt): void
    {
        if (is_a($expiresAt, DateTime::class)){
            $this->expiresAt = $expiresAt;
        } else {
            $this->expiresAt = new DateTime($expiresAt);
        }

    }

    /**
     * @return string
     */
    public function getProviderType(): string
    {
        return $this->providerType;
    }

    /**
     * @param string $providerType
     */
    public function setProviderType(string $providerType): void
    {
        $this->providerType = $providerType;
    }

    /**
     * @return string
     */
    public function getProviderId(): string
    {
        return $this->providerId;
    }

    /**
     * @param string $providerId
     */
    public function setProviderId(string $providerId): void
    {
        $this->providerId = $providerId;
    }
}