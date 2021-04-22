<?php
namespace OCA\SocialLogin\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db;
use OCP\AppFramework\Db\QBMapper;

class PublicKeyMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'sociallogin_publickeys', PublicKey::class);
    }

    /**
	 * @param string $kid
	 * @param string $issuer
	 * @return PublicKey|null
	 */
    public function find(string $kid, string $issuer) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                    $qb->expr()->eq('kid', $qb->createNamedParameter($kid))
            )
            ->andWhere($qb->expr()->eq('issuer', $qb->createNamedParameter($issuer)));

        try {
            return $this->findEntity($qb);
		} catch(Db\DoesNotExistException $e) {
			return null;
		} catch(Db\MultipleObjectsReturnedException $e) {
			$this->logger->warn("Got multiple objects when querying for signing keys. This should not happen! " . $e, ['app' => 'sociallogin']);
			return null;
		}
    }

    /**
	 * @param string $issuer
	 * @return array
	 */
    public function findAll(string $issuer) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('issuer', $qb->createNamedParameter($issuer)));

        return $this->findEntities($qb);
    }

    /**
     * Deletes all Public Keys from the specified issuer
     * @param string $issuer
     * @return array
     */
    public function deleteAll(string $issuer) {
        $qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('issuer', $qb->createNamedParameter($issuer))
			);
		$qb->execute();
    }

    public function deleteAllOlderThan(string $issuer, int $timestamp) {
        $qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('issuer', $qb->createNamedParameter($issuer))
			)
            ->andWhere(
                $qb->expr()->lte('last_updated', $qb->createNamedParameter($timestamp))
            );
		$qb->execute();
    }

}
