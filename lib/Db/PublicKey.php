<?php
namespace OCA\SocialLogin\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class PublicKey extends Entity {

    public $kid;
    public $issuer;
    public $pem;
    public $lastUpdated;

    public function __construct() {
        $this->addType('id','integer');
    }
}
