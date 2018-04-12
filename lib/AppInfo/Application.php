<?php

namespace OCA\SocialLogin\AppInfo;

use OCP\AppFramework\App;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUser;

class Application extends App
{
    private $appName = 'sociallogin';

    public function __construct()
    {
        parent::__construct($this->appName);
    }

    public function register()
    {
        $config = $this->query(IConfig::class);
        $urlGenerator = $this->query(IURLGenerator::class);
        $providers = json_decode($config->getAppValue($this->appName, 'oauth_providers', '{}'), true);
        foreach ($providers as $title=>$provider) {
            if ($provider['appid']) {
                \OC_App::registerLogIn([
                    'name' => ucfirst($title),
                    'href' => $urlGenerator->linkToRoute($this->appName.'.oAuth.login', ['provider'=>$title]),
                ]);
            }
        }
        $this->query(IUserManager::class)->listen('\OC\User', 'postSetPassword', [$this, 'postSetPassword']);
    }

    /** @internal */
    public function postSetPassword(IUser $user, $password, $recoverPassword)
    {
        $config = $this->query(IConfig::class);
        if ($config->getUserValue($user->getUID(), $this->appName, 'password')) {
            $config->setUserValue($user->getUID(), $this->appName, 'password', $password);
        }
    }

    private function query($className)
    {
        return $this->getContainer()->query($className);
    }
}
