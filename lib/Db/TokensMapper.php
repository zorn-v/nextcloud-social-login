<?php

namespace OCA\SocialLogin\Db;

use OCP\AppFramework\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

class TokensMapper extends QBMapper
{
    public function __construct(IDBConnection $db, \Psr\Log\LoggerInterface $logger) {
        parent::__construct($db, 'sociallogin_tokens', Tokens::class);
    }

    public function find(string $uid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('uid', $qb->createNamedParameter($uid))
            );

        try {
            return $this->findEntity($qb);
        } catch(Db\DoesNotExistException $e) {
            return null;
        } catch(Db\MultipleObjectsReturnedException $e) {
            $this->logger()->warn("Got multiple objects when querying for tokens. This should not happen! " . $e, ['app' => 'sociallogin']);
            return null;
        }
    }

    public function findAll(): ?array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName()
            );

        try {
            return $this->findEntities($qb);
        } catch (Exception $e) {
            return null;
        }
    }

    public function insert(Db\Entity $tokens): Db\Entity{ // TODO why would the standard method throw this error: SQLSTATE[HY000]: General error: 1 near ")": syntax error
        $qb = $this->db->getQueryBuilder();

        $qb->insert($this->getTableName());
        $qb->setValue('uid', $qb->createNamedParameter($tokens->getUid(), 'string'));
        $qb->setValue('accessToken', $qb->createNamedParameter($tokens->getAccessToken(), 'string'));
        $qb->setValue('refreshToken', $qb->createNamedParameter($tokens->getRefreshToken(), 'string'));
        $qb->setValue('expiresAt', $qb->createNamedParameter($tokens->getExpiresAt(), 'datetime'));
        $qb->setValue('providerType', $qb->createNamedParameter($tokens->getProviderType(), 'string'));
        $qb->setValue('providerId', $qb->createNamedParameter($tokens->getProviderId(), 'string'));

        $qb->executeStatement();

        return $tokens;
    }

    public function update(Db\Entity $tokens): Db\Entity{ // TODO why would the standard method throw this error: SQLSTATE[HY000]: General error: 1 near ")": syntax error
        $qb = $this->db->getQueryBuilder();

        $qb->update($this->getTableName());
        $qb->set('accessToken', $qb->createNamedParameter($tokens->getAccessToken(), 'string'));
        $qb->set('refreshToken', $qb->createNamedParameter($tokens->getRefreshToken(), 'string'));
        $qb->set('expiresAt', $qb->createNamedParameter($tokens->getExpiresAt(), 'datetime'));
        $qb->set('providerType', $qb->createNamedParameter($tokens->getProviderType(), 'string'));
        $qb->set('providerId', $qb->createNamedParameter($tokens->getProviderId(), 'string'));

        $qb->where(
            $qb->expr()->eq('uid', $qb->createNamedParameter($tokens->getUid(), 'string'))
        );

        $qb->executeStatement();

        return $tokens;
    }

}