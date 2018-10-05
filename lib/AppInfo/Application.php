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

        $container = $this->getContainer();
        $server = $container->getServer();

        $user = $server->getUserManager()->get($server->getSession()->get('user_id'));
        $isAdmin = $user !== null ? $server->getGroupManager()->isAdmin($user->getUID()) : false;
        $isSubAdmin = $user !== null ? $server->getGroupManager()->getSubAdmin()->isSubAdmin($user) : false;

        if ($isAdmin || $isSubAdmin) {
            // the app provisioning_api is responsible for the "users" rest api endpoint -> in detail we talk about the
            // class \OCA\Provisioning_API\Controller\UsersController
            // -> most of the CRUD actions in that class have the annotation @PasswordConfirmationRequired, which
            //    results in an intercepting middleware class from core during api requests
            //    -> this \OC\AppFramework\Middleware\Security\PasswordConfirmationMiddleware checks:
            //       1. the backend class name of the current user session
            //       2. the last-password-confirm timestamp value in the current user session
            //       -> if the current user is not a SAML authenticated user and the last-password-confirm timestamp
            //          is more than 30 minutes ago a \OC\AppFramework\Middleware\Security\Exceptions\NotConfirmedException
            //          will be thrown
            // => social/ OAuth2 authenticated users are a in some way like SAML authenticated users, as the real
            //    authentication is done (checked against) on another website
            //    => but the PasswordConfirmationMiddleware in the core above only checks for the "user_saml" backend
            //       class name and the last-password-confirm timestamp, to prevent from the NotConfirmedException
            //       => we don't want to change the backend class name on the fly here, because of unknown side effects,
            //          but we can temporary set the last-password-confirm session value to NOW to prevent the
            //          NotConfirmedException within the rest api context, when we want to pre-add/ update/ delete
            //          social registered users as admin via rest api
            $server->getSession()->set('last-password-confirm', time());
        }
    }

    public function register()
    {
        \OCP\Util::addStyle($this->appName, 'style');

        $this->config = $this->query(IConfig::class);

        \OCP\App::registerPersonal($this->appName, 'appinfo/personal');

        $this->query(IUserManager::class)->listen('\OC\User', 'preDelete', [$this, 'preDeleteUser']);

        $userSession = $this->query(IUserSession::class);
        if ($userSession->isLoggedIn()) {
            $uid = $userSession->getUser()->getUID();
            if ($this->config->getUserValue($uid, $this->appName, 'disable_password_confirmation')) {
                $this->query(ISession::class)->set('last-password-confirm', time());
            }
            return;
        }

        $this->urlGenerator = $this->query(IURLGenerator::class);
        $request = $this->query(IRequest::class);
        $this->redirectUrl = $request->getParam('redirect_url');

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
                        'name' => ucfirst($name),
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
        $providers = json_decode($this->config->getAppValue($this->appName, $providersType.'_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                ++$this->providersCount;
                $this->providerUrl = $this->urlGenerator->linkToRoute($this->appName.'.login.'.$providersType, [
                    'provider' => $provider['name'],
                    'login_redirect_url' => $this->redirectUrl
                ]);
                \OC_App::registerLogIn([
                    'name' => $provider['title'],
                    'href' => $this->providerUrl,
                ]);
            }
        }
    }

    private function query($className)
    {
        return $this->getContainer()->query($className);
    }
}
