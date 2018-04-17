<?php

namespace OCA\SocialLogin\Db;

use OCP\IDBConnection;

class SocialConnectDAO
{

    private $db;

    public function __construct(IDBConnection $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $identifier social login identifier
     * @return string|null User uid
     */
    public function findUID($identifier)
    {
        $sql = 'SELECT * FROM `*PREFIX*sociallogin_connect` ' .
            'WHERE `identifier` = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $identifier);
        $stmt->execute();

        $row = $stmt->fetch();

        $stmt->closeCursor();

        return $row ? $row['uid'] : null;
    }

    public function connectLogin($uid, $identifier)
    {
        $sql = 'INSERT INTO `*PREFIX*sociallogin_connect`(`uid`, `identifier`) VALUES(?, ?)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $uid);
        $stmt->bindParam(2, $identifier);
        $stmt->execute();
    }

    public function disconnectLogin($identifier)
    {
        $sql = 'DELETE FROM `*PREFIX*sociallogin_connect` WHERE `identifier` = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $identifier);
        $stmt->execute();
    }

    public function disconnectAll($uid)
    {
        $sql = 'DELETE FROM `*PREFIX*sociallogin_connect` WHERE `uid` = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $uid);
        $stmt->execute();
    }

    /**
     * @param string $uid
     * @return array
     */
    public function getConnectedLogins($uid)
    {
        $sql = 'SELECT * FROM `*PREFIX*sociallogin_connect` ' .
            'WHERE `uid` = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $uid);
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = $row['identifier'];
        }
        $stmt->closeCursor();

        return $result;
    }
}
