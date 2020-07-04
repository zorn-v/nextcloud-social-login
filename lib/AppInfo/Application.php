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
use OCP\IL10N;
use OCA\SocialLogin\Db\SocialConnectDAO;
use OCP\Util;

class Application extends App
{
    private $appName = 'sociallogin';

    /** @var IConfig */
    private $config;
    /** @var IURLGenerator */
    private $urlGenerator;

    public function __construct()
    {
        parent::__construct($this->appName);
    }

    public function register()
    {
        Util::addStyle($this->appName, 'style');
        $l = $this->query(IL10N::class);

        $this->config = $this->query(IConfig::class);

        $this->query(IUserManager::class)->listen('\OC\User', 'preDelete', [$this, 'preDeleteUser']);

        $userSession = $this->query(IUserSession::class);
        if ($userSession->isLoggedIn()) {
            $uid = $userSession->getUser()->getUID();
            $session = $this->query(ISession::class);
            if ($this->config->getUserValue($uid, $this->appName, 'disable_password_confirmation')) {
                $session->set('last-password-confirm', time());
            }
            if ($logoutUrl = $session->get('sociallogin_logout_url')) {
                $userSession->listen('\OC\User', 'postLogout', function () use ($logoutUrl) {
                    header('Location: ' . $logoutUrl);
                    exit();
                });
            }
            return;
        }

        $this->urlGenerator = $this->query(IURLGenerator::class);
        $request = $this->query(IRequest::class);
        $redirectUrl = $request->getParam('redirect_url');

        $providersCount = 0;
        $providerUrl = '';
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers'), true) ?: [];
        foreach ($providers as $name => $provider) {
            if ($provider['appid']) {
                $providerUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.oauth', [
                    'provider' => $name,
                    'login_redirect_url' => $redirectUrl
                ]);
                if ($name === 'telegram') {
                    $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
                    $csp->addAllowedScriptDomain('telegram.org')
                        ->addAllowedFrameDomain('oauth.telegram.org')
                    ;
                    $manager = \OC::$server->getContentSecurityPolicyManager();
                    $manager->addDefaultPolicy($csp);

                    Util::addHeader('meta', [
                        'id' => 'tg-data',
                        'data-login' => $provider['appid'],
                        'data-redirect-url' => $providerUrl,
                    ]);
                    Util::addScript($this->appName, 'telegram');
                    continue;
                }
                ++$providersCount;
                \OC_App::registerLogIn([
                    'name' => $l->t('Log in with %s', ucfirst($name)),
                    'href' => $providerUrl,
                ]);
            }
        }

        $providers = json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true) ?: [];
        foreach ($providers as $providersType => $providerList) {
            foreach ($providerList as $provider) {
                ++$providersCount;
                $providerUrl = $this->urlGenerator->linkToRoute($this->appName.'.login.custom', [
                    'type' => $providersType,
                    'provider' => $provider['name'],
                    'login_redirect_url' => $redirectUrl
                ]);
                \OC_App::registerLogIn([
                    'name' => $l->t('Log in with %s', $provider['title']),
                    'href' => $providerUrl,
                    'style' => isset($provider['style']) ? $provider['style'] : '',
                ]);
            }
        }

        $useLoginRedirect = $providersCount === 1
            && PHP_SAPI !== 'cli'
            && $request->getMethod() === 'GET'
            && !$request->getParam('noredir')
            && $this->config->getSystemValue('social_login_auto_redirect', false);
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
