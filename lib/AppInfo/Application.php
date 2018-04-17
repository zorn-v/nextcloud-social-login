<?php

namespace OCA\SocialLogin\AppInfo;

use OCP\AppFramework\App;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUser;
use OCA\SocialLogin\Db\SocialConnectDAO;

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
        $providers = json_decode($config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $title=>$provider) {
                if ($provider['appid']) {
                    \OC_App::registerLogIn([
                        'name' => ucfirst($title),
                        'href' => $urlGenerator->linkToRoute($this->appName.'.login.oauth', ['provider'=>$title]),
                    ]);
                }
            }
        }
        $providers = json_decode($config->getAppValue($this->appName, 'openid_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                \OC_App::registerLogIn([
                    'name' => ucfirst($provider['title']),
                    'href' => $urlGenerator->linkToRoute($this->appName.'.login.openid', ['provider'=>$provider['title']]),
                ]);
            }
        }
        if ($config->getAppValue($this->appName, 'allow_login_connect')) {
            \OCP\App::registerPersonal($this->getContainer()->getAppName(), 'appinfo/personal');
        }

        $this->query(IUserManager::class)->listen('\OC\User', 'preDelete', [$this, 'preDeleteUser']);
    }

    public function preDeleteUser(IUser $user)
    {
        $this->query(SocialConnectDAO::class)->disconnectAll($user->getUID());
    }

    private function query($className)
    {
        return $this->getContainer()->query($className);
    }
}
