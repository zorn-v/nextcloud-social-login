<?php

namespace OCA\SocialLogin\AppInfo;

use OCP\AppFramework\App;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IRequest;
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

        $providersCount = 0;
        $providerUrl = '';
        $providers = json_decode($config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $title=>$provider) {
                if ($provider['appid']) {
                    ++$providersCount;
                    $providerUrl = $urlGenerator->linkToRoute($this->appName.'.login.oauth', ['provider'=>$title]);
                    \OC_App::registerLogIn([
                        'name' => ucfirst($title),
                        'href' => $providerUrl,
                    ]);
                }
            }
        }
        $providers = json_decode($config->getAppValue($this->appName, 'openid_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                ++$providersCount;
                $providerUrl = $urlGenerator->linkToRoute($this->appName.'.login.openid', ['provider'=>$provider['title']]);
                \OC_App::registerLogIn([
                    'name' => ucfirst($provider['title']),
                    'href' => $providerUrl,
                ]);
            }
        }
        $providers = json_decode($config->getAppValue($this->appName, 'custom_oidc_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                ++$providersCount;
                $providerUrl = $urlGenerator->linkToRoute($this->appName.'.login.custom_oidc', ['provider'=>$provider['title']]);
                \OC_App::registerLogIn([
                    'name' => ucfirst($provider['title']),
                    'href' => $providerUrl,
                ]);
            }
        }

        $useLoginRedirect = $providersCount === 1 && $config->getSystemValue('social_login_auto_redirect', false);
        if ($useLoginRedirect && $this->query(IRequest::class)->getPathInfo() === '/login' && !$this->query(IUserSession::class)->isLoggedIn()) {
            header('Location: ' . $providerUrl);
            exit();
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
