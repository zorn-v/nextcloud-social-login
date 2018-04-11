<?php

namespace OCA\SocialLogin\Storage;

use Hybridauth\Storage\Session;

class SessionStorage extends Session
{
    /**
    * {@inheritdoc}
    */
    public function set($key, $value)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        parent::set($key, $value);
    }
}
