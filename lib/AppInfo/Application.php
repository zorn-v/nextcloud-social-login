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

    private $providersCount = 0;

    private $providerUrl;

    private $redirectUrl;
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
        $this->redirectUrl = $request->getParam('redirect_url');

        if ($tgBot = $this->config->getAppValue($this->appName, 'tg_bot')) {
            $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
            $csp->addAllowedScriptDomain('telegram.org')
                ->addAllowedFrameDomain('oauth.telegram.org')
            ;
            $manager = \OC::$server->getContentSecurityPolicyManager();
            $manager->addDefaultPolicy($csp);

            Util::addHeader('tg-data', [
                'data-login' => $tgBot,
                'data-redirect-url' => $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.telegram', ['login_redirect_url' => $this->redirectUrl]),
            ]);
            Util::addScript($this->appName, 'telegram');
        }

        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $name => $provider) {
                if ($provider['appid']) {
                    ++$this->providersCount;
                    $this->providerUrl = $this->urlGenerator->linkToRoute($this->appName.'.login.oauth', [
                        'provider' => $name,
                        'login_redirect_url' => $this->redirectUrl
                    ]);
                    \OC_App::registerLogIn([
                        'name' => $l->t('Log in with %s', ucfirst($name)),
                        'href' => $this->providerUrl,
                    ]);
                }
            }
        }

        $this->addAltLogins('openid');
        $this->addAltLogins('custom_oidc');
        $this->addAltLogins('custom_oauth2');

        $useLoginRedirect = $this->providersCount === 1
            && PHP_SAPI !== 'cli'
            && $request->getMethod() === 'GET'
            && !$request->getParam('noredir')
            && $this->config->getSystemValue('social_login_auto_redirect', false);
        if ($useLoginRedirect && $request->getPathInfo() === '/login') {
            header('Location: ' . $this->providerUrl);
            exit();
        }
    }

    public function preDeleteUser(IUser $user)
    {
        $this->query(SocialConnectDAO::class)->disconnectAll($user->getUID());
    }

    private function addAltLogins($providersType)
    {
        $l = $this->query(IL10N::class);
        $providers = json_decode($this->config->getAppValue($this->appName, $providersType.'_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                ++$this->providersCount;
                $this->providerUrl = $this->urlGenerator->linkToRoute($this->appName.'.login.'.$providersType, [
                    'provider' => $provider['name'],
                    'login_redirect_url' => $this->redirectUrl
                ]);
                \OC_App::registerLogIn([
                    'name' => $l->t('Log in with %s', $provider['title']),
                    'href' => $this->providerUrl,
                    'style' => isset($provider['style']) ? $provider['style'] : '',
                ]);
            }
        }
    }

    private function query($className)
    {
        return $this->getContainer()->query($className);
    }
}
