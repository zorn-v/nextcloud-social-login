<?php

namespace OCA\SocialLogin\AppInfo;

use OCP\AppFramework\App;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IRequest;
use OCP\ISession;
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
        \OCP\Util::addStyle($this->appName, 'style');

        $config = $this->query(IConfig::class);

        \OCP\App::registerPersonal($this->appName, 'appinfo/personal');

        $this->query(IUserManager::class)->listen('\OC\User', 'preDelete', [$this, 'preDeleteUser']);

        $userSession = $this->query(IUserSession::class);
        if ($userSession->isLoggedIn()) {
            $uid = $userSession->getUser()->getUID();
            if ($config->getUserValue($uid, $this->appName, 'disable_password_confirmation')) {
                $this->query(ISession::class)->set('last-password-confirm', time());
            }
            return;
        }

        $urlGenerator = $this->query(IURLGenerator::class);
        $request = $this->query(IRequest::class);
        $redirectUrl = $request->getParam('redirect_url');

        $providersCount = 0;
        $providerUrl = '';
        $providers = json_decode($config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $name=>$provider) {
                if ($provider['appid']) {
                    ++$providersCount;
                    $providerUrl = $urlGenerator->linkToRoute($this->appName.'.login.oauth', ['provider'=>$name, 'redirect_url'=>$redirectUrl]);
                    \OC_App::registerLogIn([
                        'name' => ucfirst($name),
                        'href' => $providerUrl,
                    ]);
                }
            }
        }
        $providers = json_decode($config->getAppValue($this->appName, 'openid_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                ++$providersCount;
                $providerUrl = $urlGenerator->linkToRoute($this->appName.'.login.openid', ['provider'=>$provider['name'], 'redirect_url'=>$redirectUrl]);
                \OC_App::registerLogIn([
                    'name' => $provider['title'],
                    'href' => $providerUrl,
                ]);
            }
        }
        $providers = json_decode($config->getAppValue($this->appName, 'custom_oidc_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                ++$providersCount;
                $providerUrl = $urlGenerator->linkToRoute($this->appName.'.login.custom_oidc', ['provider'=>$provider['name'], 'redirect_url'=>$redirectUrl]);
                \OC_App::registerLogIn([
                    'name' => $provider['title'],
                    'href' => $providerUrl,
                ]);
            }
        }

        $useLoginRedirect = $providersCount === 1 && $config->getSystemValue('social_login_auto_redirect', false);
        if ($useLoginRedirect && $request->getPathInfo() === '/login') {
            header('Location: ' . $providerUrl);
            exit();
        }
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
