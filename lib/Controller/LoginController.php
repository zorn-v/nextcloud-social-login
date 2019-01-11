<?php

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IURLGenerator;
use OCP\IAvatarManager;
use OCP\IGroupManager;
use OCP\ISession;
use OC\User\LoginException;
use OCA\SocialLogin\Storage\SessionStorage;
use OCA\SocialLogin\Provider\CustomOAuth2;
use OCA\SocialLogin\Provider\CustomOpenIDConnect;
use OCA\SocialLogin\Db\SocialConnectDAO;
use Hybridauth\Provider;
use Hybridauth\User\Profile;
use Hybridauth\HttpClient\Curl;
use Hybridauth\Data;

class LoginController extends Controller
{
    /** @var IConfig */
    private $config;
    /** @var IURLGenerator */
    private $urlGenerator;
    /** @var SessionStorage */
    private $storage;
    /** @var IUserManager */
    private $userManager;
    /** @var IUserSession */
    private $userSession;
    /** @var IAvatarManager */
    private $avatarManager;
    /** @var IGroupManager */
    private $groupManager;
    /** @var ISession */
    private $session;
    /** @var IL10N */
    private $l;
    /** @var SocialConnectDAO */
    private $socialConnect;


    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        IURLGenerator $urlGenerator,
        SessionStorage $storage,
        IUserManager $userManager,
        IUserSession $userSession,
        IAvatarManager $avatarManager,
        IGroupManager $groupManager,
        ISession $session,
        IL10N $l,
        SocialConnectDAO $socialConnect
    ) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->storage = $storage;
        $this->userManager = $userManager;
        $this->userSession = $userSession;
        $this->avatarManager = $avatarManager;
        $this->groupManager = $groupManager;
        $this->session = $session;
        $this->l = $l;
        $this->socialConnect = $socialConnect;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function oauth($provider)
    {
        $scopes = [
            'facebook' => 'email, public_profile',
        ];
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        if (is_array($providers) && in_array($provider, array_keys($providers))) {
            foreach ($providers as $name => $prov) {
                if ($name === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.oauth', ['provider' => $provider]);
                    $config = [
                        'callback' => $callbackUrl,
                        'keys'     => [
                            'id'     => $prov['appid'],
                            'secret' => $prov['secret'],
                        ],
                    ];
                    if (isset($scopes[$provider])) {
                        $config['scope'] = $scopes[$provider];
                    }
                    if (isset($prov['auth_params']) && is_array($prov['auth_params'])) {
                        foreach ($prov['auth_params'] as $k => $v) {
                            if (!empty($v)) {
                                $config['authorize_url_parameters'][$k] = $v;
                            }
                        }
                    }
                    break;
                }
            }
        }
        return $this->auth(Provider::class.'\\'.ucfirst($provider), $config, $provider, 'OAuth');
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function openid($provider)
    {
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'openid_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $prov) {
                if ($prov['name'] === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.openid', ['provider' => $provider]);
                    $config = [
                        'callback'          => $callbackUrl,
                        'openid_identifier' => $prov['url'],
                    ];
                    break;
                }
            }
        }
        return $this->auth(Provider\OpenID::class, $config, $provider, 'OpenID');
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function customOidc($provider)
    {
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'custom_oidc_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $prov) {
                if ($prov['name'] === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.custom_oidc', ['provider' => $provider]);
                    $config = [
                        'callback' => $callbackUrl,
                        'scope' => $prov['scope'],
                        'keys' => [
                            'id'     => $prov['clientId'],
                            'secret' => $prov['clientSecret'],
                        ],
                        'endpoints' => new Data\Collection([
                            'authorize_url'    => $prov['authorizeUrl'],
                            'access_token_url' => $prov['tokenUrl'],
                            'user_info_url'    => $prov['userInfoUrl'],
                            'api_base_url'     => '',
                        ]),
                    ];
                    break;
                }
            }
        }
        return $this->auth(CustomOpenIDConnect::class, $config, $provider, 'OpenID Connect');
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function customOauth2($provider)
    {
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'custom_oauth2_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $prov) {
                if ($prov['name'] === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.custom_oauth2', ['provider' => $provider]);
                    $config = [
                        'callback' => $callbackUrl,
                        'scope' => $prov['scope'],
                        'keys' => [
                            'id'     => $prov['clientId'],
                            'secret' => $prov['clientSecret'],
                        ],
                        'endpoints' => new Data\Collection([
                            'api_base_url'     => $prov['apiBaseUrl'],
                            'authorize_url'    => $prov['authorizeUrl'],
                            'access_token_url' => $prov['tokenUrl'],
                            'profile_url'      => $prov['profileUrl'],
                        ]),
                        'profile_fields'   => $prov['profileFields'],
                    ];
                    break;
                }
            }
        }
        return $this->auth(CustomOAuth2::class, $config, $provider, 'Custom OAuth2');
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function telegram()
    {
        if ($redirectUrl = $this->request->getParam('login_redirect_url')) {
            $this->session->set('login_redirect_url', $redirectUrl);
        }
        $botToken = $this->config->getAppValue($this->appName, 'tg_token');
        $checkHash = $this->request->getParam('hash');
        $authData = $_GET;
        unset($authData['hash'], $authData['login_redirect_url']);
        ksort($authData);
        array_walk($authData, function (&$value, $key) {$value = $key.'='.$value;});
        $dataCheckStr = implode("\n", $authData);
        $secretKey = hash('sha256', $botToken, true);
        $hash = hash_hmac('sha256', $dataCheckStr, $secretKey);
        if ($hash !== $checkHash) {
            throw new LoginException($this->l->t('Telegram auth data check failed'));
        }
        if ((time() - $this->request->getParam('auth_date')) > 300) {
            throw new LoginException($this->l->t('Telegram auth data expired'));
        }
        if (null === $tgId = $this->request->getParam('id')) {
            throw new LoginException($this->l->t('Missing mandatory "id" param'));
        }
        $uid = 'tg-' . $tgId;
        $profile = new Profile();
        $profile->identifier = $tgId;
        $profile->displayName = $this->request->getParam('first_name').' '.$this->request->getParam('last_name');
        $profile->photoURL = $this->request->getParam('photo_url');
        return $this->login($uid, $profile);
    }

    private function auth($class, array $config, $provider, $providerTitle)
    {
        if (empty($config)) {
            throw new LoginException($this->l->t('Unknown %s provider: "%s"', [$providerTitle, $provider]));
        }
        if ($redirectUrl = $this->request->getParam('login_redirect_url')) {
            $this->session->set('login_redirect_url', $redirectUrl);
        }
        try {
            $adapter = new $class($config, null, $this->storage);
            $adapter->authenticate();
            $profile = $adapter->getUserProfile();
        }  catch (\Exception $e) {
            throw new LoginException($e->getMessage());
        }
        $profileId = preg_replace('#.*/#', '', rtrim($profile->identifier, '/'));
        if (empty($profileId)) {
            throw new LoginException($this->l->t('Can not get identifier from provider'));
        }
        $uid = $provider.'-'.$profileId;
        if (strlen($uid) > 64) {
            $uid = $provider.'-'.md5($profileId);
        }
        return $this->login($uid, $profile);
    }

    private function login($uid, Profile $profile)
    {
        $user = $this->userManager->get($uid);
        if (null === $user) {
            $connectedUid = $this->socialConnect->findUID($uid);
            $user = $this->userManager->get($connectedUid);
        }
        if ($this->userSession->isLoggedIn()) {
            if (!$this->config->getAppValue($this->appName, 'allow_login_connect')) {
                throw new LoginException($this->l->t('Social login connect is disabled'));
            }
            if (null !== $user) {
                throw new LoginException($this->l->t('This account already connected'));
            }
            $currentUid = $this->userSession->getUser()->getUID();
            $this->socialConnect->connectLogin($currentUid, $uid);
            return new RedirectResponse($this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section'=>'additional']));
        }
        if (null === $user) {
            if ($this->config->getAppValue($this->appName, 'disable_registration')) {
                throw new LoginException($this->l->t('Auto creating new users is disabled'));
            }
            if (
                $profile->email && $this->config->getAppValue($this->appName, 'prevent_create_email_exists')
                && count($this->userManager->getByEmail($profile->email)) !== 0
            ) {
                throw new LoginException($this->l->t('Email already registered'));
            }
            $password = substr(base64_encode(random_bytes(64)), 0, 30);
            $user = $this->userManager->createUser($uid, $password);
            $user->setDisplayName($profile->displayName ?: $profile->identifier);
            $user->setEMailAddress((string)$profile->email);

            $newUserGroup = $this->config->getAppValue($this->appName, 'new_user_group');
            if ($newUserGroup) {
                try {
                    $group = $this->groupManager->get($newUserGroup);
                    $group->addUser($user);
                } catch (\Exception $e) {}
            }

            if ($profile->photoURL) {
                $curl = new Curl();
                try {
                    $photo = $curl->request($profile->photoURL);
                    $avatar = $this->avatarManager->getAvatar($uid);
                    $avatar->set($photo);
                } catch (\Exception $e) {}
            }
            $this->config->setUserValue($uid, $this->appName, 'disable_password_confirmation', 1);
        }

        $this->userSession->completeLogin($user, ['loginName' => $user->getUID(), 'password' => null]);
        $this->userSession->createSessionToken($this->request, $user->getUID(), $user->getUID());

        if ($redirectUrl = $this->session->get('login_redirect_url')) {
            return new RedirectResponse($redirectUrl);
        }

        $this->session->set('last-password-confirm', time());

        return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
    }

    private function getClientName() {
        $userAgent = $this->request->getHeader('USER_AGENT');
        return $userAgent !== null ? $userAgent : 'unknown';
    }
}
