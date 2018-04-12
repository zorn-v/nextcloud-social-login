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

    /**
    * {@inheritdoc}
    */
    public function delete($key)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        parent::delete($key);
    }

    /**
    * {@inheritdoc}
    */
    public function deleteMatch($key)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        parent::deleteMatch($key);
    }
}
