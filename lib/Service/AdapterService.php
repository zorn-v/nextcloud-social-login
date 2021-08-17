<?php

namespace OCA\SocialLogin\Service;

class AdapterService
{
    /**
     * @throws \Exception
     */
    public function new($class, $config, $storage){

        $adapter = new $class($config, null, $storage);
        return $adapter;
    }
}