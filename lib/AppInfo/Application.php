<?php

namespace OCA\SocialLogin\AppInfo;

use OCA\SocialLogin\Db\SocialConnectDAO;
use OCA\SocialLogin\Service\ProviderService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserLoggedOutEvent;
use OCP\Util;

class Application extends App implements IBootstrap
{
    private $appName = 'sociallogin';

    public function __construct()
    {
        parent::__construct($this->appName);
    }

    public function register(IRegistrationContext $context): void
    {
        require __DIR__ . '/../../3rdparty/autoload.php';
    }

    public function boot(IBootContext $context): void
    {
        Util::addStyle($this->appName, 'styles');

        $l = $this->query(IL10N::class);
        $config = $this->query(IConfig::class);

        $dispatcher = $this->query(IEventDispatcher::class);
        $dispatcher->addListener(BeforeUserDeletedEvent::class, [$this, 'preDeleteUser']);

        $userSession = $this->query(IUserSession::class);
        if ($userSession->isLoggedIn()) {
            $uid = $userSession->getUser()->getUID();
            $session = $this->query(ISession::class);
            if ($config->getUserValue($uid, $this->appName, 'disable_password_confirmation')) {
                $session->set('last-password-confirm', time());
            }
            if ($logoutUrl = $session->get('sociallogin_logout_url')) {
                $dispatcher->addListener(UserLoggedOutEvent::class, function () use ($logoutUrl) {
                    header('Location: ' . $logoutUrl);
                    exit();
                });
            }
            return;
        }

        $providerService = $this->query(ProviderService::class);
        $urlGenerator = $this->query(IURLGenerator::class);
        $request = $this->query(IRequest::class);
        $redirectUrl = $request->getParam('redirect_url');

        $providersCount = 0;
        $authUrl = '';
        $providers = json_decode($config->getAppValue($this->appName, 'oauth_providers'), true) ?: [];
        foreach ($providers as $name => $provider) {
            if ($provider['appid'] && $authUrl = $providerService->getAuthUrl($name, $provider['appid'])) {
                ++$providersCount;
                \OC_App::registerLogIn([
                    'href' => $authUrl,
                    'name' => $l->t('Log in with %s', ucfirst($name)),
                ]);
            }
        }

        $providers = json_decode($config->getAppValue($this->appName, 'custom_providers'), true) ?: [];
        foreach ($providers as $providersType => $providerList) {
            foreach ($providerList as $provider) {
                ++$providersCount;
                $authUrl = $urlGenerator->linkToRoute($this->appName.'.login.custom', [
                    'type' => $providersType,
                    'provider' => $provider['name'],
                    'login_redirect_url' => $redirectUrl
                ]);
                \OC_App::registerLogIn([
                    'href' => $authUrl,
                    'name' => $l->t('Log in with %s', $provider['title']),
                    'style' => isset($provider['style']) ? $provider['style'] : '',
                ]);
            }
        }

        $useLoginRedirect = $providersCount === 1
            && PHP_SAPI !== 'cli'
            && $request->getMethod() === 'GET'
            && !$request->getParam('noredir')
            && $config->getSystemValue('social_login_auto_redirect', false);
        if ($useLoginRedirect && $request->getPathInfo() === '/login') {
            header('Location: ' . $authUrl);
            exit();
        }

        $hideDefaultLogin = $providersCount > 0
            && !$request->getParam('showDefault')
            && $config->getAppValue($this->appName, 'hide_default_login');
        if ($hideDefaultLogin && $request->getPathInfo() === '/login') {
            Util::addStyle($this->appName, 'hide_default_login');
            \OC_App::registerLogin([
                'href' => '#body-login',
                'name' => $l->t('Log in with username or email'),
            ]);
        }
    }

    public function preDeleteUser(BeforeUserDeletedEvent $event)
    {
        $user = $event->getUser();
        $this->query(SocialConnectDAO::class)->disconnectAll($user->getUID());
    }

    private function query($className)
    {
        return $this->getContainer()->query($className);
    }
}
