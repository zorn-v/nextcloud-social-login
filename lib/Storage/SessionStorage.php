<?php

namespace OCA\SocialLogin\Storage;

use Hybridauth\Storage\Session;

class SessionStorage extends Session
{
    /**
    * {@inheritdoc}
    */
    public function get($key)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return parent::get($key);
    }

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

    /**
    * {@inheritdoc}
    */
    public function clear()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        parent::clear();
    }
}
