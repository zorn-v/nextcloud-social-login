<?php

namespace OCA\SocialLogin\Storage;

use OCP\ISession;
use Hybridauth\Storage\StorageInterface;

class SessionStorage implements StorageInterface
{
    /** @var ISession */
    private $session;
    private $ns = 'HA-STORAGE';

    public function __construct(ISession $session)
    {
        $this->session = $session;
    }

    /**
    * {@inheritdoc}
    */
    public function get($key)
    {
        error_log("GET $key");
        $values = $this->session->get($this->ns);
        return $values[$key];
    }

    /**
    * {@inheritdoc}
    */
    public function set($key, $value)
    {
        error_log("SET $key = $value");
        $values = $this->session->get($this->ns);
        $values[$key] = $value;
        $this->session->set($this->ns, $values);
    }

    /**
    * {@inheritdoc}
    */
    public function delete($key)
    {
        error_log("DELETE $key");
        $values = $this->session->get($this->ns);
        unset($values[$key]);
        $this->session->set($this->ns, $values);
    }

    /**
    * {@inheritdoc}
    */
    public function deleteMatch($key)
    {
        error_log("DELETE MATCH $key");
        $values = $this->session->get($this->ns);
        foreach ($values as $k=>$v) {
            if (strstr($k, $key)) {
                unset($values[$key]);
            }
        }
        $this->session->set($this->ns, $values);
    }

    /**
    * {@inheritdoc}
    */
    public function clear()
    {
        error_log("CLEAR");
        $this->session->set($this->ns, []);
    }
}
