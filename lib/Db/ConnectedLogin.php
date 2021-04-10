<?php
namespace OCA\SocialLogin\Db;

use OCP\AppFramework\Db\Entity;

class ConnectedLogin extends Entity {

    public $uid;
    public $identifier;
}
