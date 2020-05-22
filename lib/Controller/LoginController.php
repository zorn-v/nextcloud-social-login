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
use OCP\Mail\IMailer;
use OC\User\LoginException;
use OCA\SocialLogin\Storage\SessionStorage;
use OCA\SocialLogin\Provider\CustomOAuth2;
use OCA\SocialLogin\Provider\CustomOpenIDConnect;
use OCA\SocialLogin\Db\SocialConnectDAO;
use Hybridauth\Provider;
use Hybridauth\User\Profile;
use Hybridauth\HttpClient\Curl;

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
    /** @var IMailer */
    private $mailer;
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
        IMailer $mailer,
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
        $this->mailer = $mailer;
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
                        'default_group' => $prov['defaultGroup'],
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
                        'default_group'     => $prov['defaultGroup'],
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
                    list($authUrl, $authQuery) = explode('?', $prov['authorizeUrl']) + [1 => null];
                    $config = [
                        'callback' => $callbackUrl,
                        'scope' => $prov['scope'],
                        'keys' => [
                            'id'     => $prov['clientId'],
                            'secret' => $prov['clientSecret'],
                        ],
                        'endpoints' => [
                            'authorize_url'    => $authUrl,
                            'access_token_url' => $prov['tokenUrl'],
                            'user_info_url'    => $prov['userInfoUrl'],
                        ],
                        'default_group' => $prov['defaultGroup'],
                        'groups_claim'  => isset($prov['groupsClaim']) ? $prov['groupsClaim'] : null,
                        'group_mapping' => isset($prov['groupMapping']) ? $prov['groupMapping'] : null,
                        'logout_url'    => isset($prov['logoutUrl']) ? $prov['logoutUrl'] : null,
                    ];
                    if ($authQuery) {
                        parse_str($authQuery, $config['authorize_url_parameters']);
                    }
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
                        'endpoints' => [
                            'api_base_url'     => $prov['apiBaseUrl'],
                            'authorize_url'    => $prov['authorizeUrl'],
                            'access_token_url' => $prov['tokenUrl'],
                            'profile_url'      => $prov['profileUrl'],
                        ],
                        'profile_fields' => $prov['profileFields'],
                        'default_group'  => $prov['defaultGroup'],
                        'groups_claim'  => isset($prov['groupsClaim']) ? $prov['groupsClaim'] : null,
                        'group_mapping' => isset($prov['groupMapping']) ? $prov['groupMapping'] : null,
                        'logout_url'    => isset($prov['logoutUrl']) ? $prov['logoutUrl'] : null,
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
        $profile->data['default_group'] = $this->config->getAppValue($this->appName, 'tg_group');
        return $this->login($uid, $profile);
    }

    private function auth($class, array $config, $provider, $providerType)
    {
        if (empty($config)) {
            throw new LoginException($this->l->t('Unknown %s provider: "%s"', [$providerType, $provider]));
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

        if (!empty($config['authorize_url_parameters']['hd'])) {
            $profileHd = preg_match('#@(.+)#', $profile->email, $m) ? $m[1] : null;
            $allowedHd = array_map('trim', explode(',', $config['authorize_url_parameters']['hd']));
            if (!in_array($profileHd, $allowedHd)) {
                $this->storage->clear();
                throw new LoginException($this->l->t('Login from %s domain is not allowed for %s provider', [$profileHd, $provider]));
            }
        }

        if (!empty($config['logout_url'])) {
            $this->session->set('sociallogin_logout_url', $config['logout_url']);
        } else {
            $this->session->remove('sociallogin_logout_url');
        }

        $profile->data['default_group'] = $config['default_group'];

        $uid = $provider.'-'.$profileId;
        if (strlen($uid) > 64 || !preg_match('#^[a-z0-9_.@-]+$#i', $profileId)) {
            $uid = $provider.'-'.md5($profileId);
        }
        return $this->login($uid, $profile, $provider.'-');
    }

    private function login($uid, Profile $profile, $newGroupPrefix = '')
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
            return new RedirectResponse($this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section'=>'sociallogin']));
        }

        if ($this->config->getAppValue($this->appName, 'restrict_users_wo_mapped_groups') && isset($profile->data['group_mapping'])) {
            $groups = isset($profile->data['groups']) ? $profile->data['groups'] : [];
            $mappedGroups = array_intersect($groups, array_keys($profile->data['group_mapping']));
            if (!$mappedGroups) {
                throw new LoginException($this->l->t('Your user group is not allowed to login, please contact support'));
            }
        }

        $updateUserProfile = $this->config->getAppValue($this->appName, 'update_profile_on_login');

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

            if ($this->config->getAppValue($this->appName, 'create_disabled_users')) {
                $user->setEnabled(false);
            }

            $this->config->setUserValue($uid, $this->appName, 'disable_password_confirmation', 1);
            $updateUserProfile = true;

            if (!$this->config->getAppValue($this->appName, 'disable_notify_admins')) {
                $this->notifyAdmins($uid, $profile->displayName ?: $profile->identifier, $profile->data['default_group']);
            }
        }

        if ($updateUserProfile) {
            $user->setDisplayName($profile->displayName ?: $profile->identifier);
            $user->setEMailAddress((string)$profile->email);

            if ($profile->photoURL) {
                $curl = new Curl();
                try {
                    $photo = $curl->request($profile->photoURL);
                    $avatar = $this->avatarManager->getAvatar($uid);
                    $avatar->set($photo);
                } catch (\Exception $e) {}
            }

            if (isset($profile->data['groups']) && is_array($profile->data['groups'])) {
                $groupNames = $profile->data['groups'];
                $groupMapping = isset($profile->data['group_mapping']) ? $profile->data['group_mapping'] : null;
                $userGroups = $this->groupManager->getUserGroups($user);
                $autoCreateGroups = $this->config->getAppValue($this->appName, 'auto_create_groups');
                $syncGroupNames = [];

                foreach ($groupNames as $k => $v) {
                    if ($groupMapping && isset($groupMapping[$v])) {
                        $syncGroupNames[] = $groupMapping[$v];
                    }
                    if ($autoCreateGroups) {
                        $syncGroupNames[] = $newGroupPrefix.$v;
                    }
                }

                if (!$this->config->getAppValue($this->appName, 'no_prune_user_groups')) {
                    foreach ($userGroups as $group) {
                        if (!in_array($group->getGID(), $syncGroupNames)) {
                            $group->removeUser($user);
                        }
                    }
                }

                foreach ($syncGroupNames as $groupName) {
                    if ($group = $this->groupManager->createGroup($groupName)) {
                        $group->addUser($user);
                    }
                }

            }

            $defaultGroup = $profile->data['default_group'];
            if ($defaultGroup && $group = $this->groupManager->get($defaultGroup)) {
                $group->addUser($user);
            }
        }


        $this->userSession->completeLogin($user, ['loginName' => $user->getUID(), 'password' => '']);
        $this->userSession->createSessionToken($this->request, $user->getUID(), $user->getUID());

        if ($redirectUrl = $this->session->get('login_redirect_url')) {
            return new RedirectResponse($redirectUrl);
        }

        $this->session->set('last-password-confirm', time());

        return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
    }

    private function notifyAdmins($uid, $displayName, $groupId)
    {
        $admins = $this->groupManager->get('admin')->getUsers();
        if ($groupId) {
            $group = $this->groupManager->get($groupId);
            $subAdmins = $this->groupManager->getSubAdmin()->getGroupsSubAdmins($group);
            foreach ($subAdmins as $user) {
                if (!in_array($user, $admins)) {
                    $admins[] = $user;
                }
            }
        }

        $sendTo = [];
        foreach ($admins as $user) {
            $email = $user->getEMailAddress();
            if ($email && $user->isEnabled()) {
                $sendTo[$email] = $user->getDisplayName() ?: $user->getUID();
            }
        }

        if ($sendTo) {
            $template = $this->mailer->createEMailTemplate('sociallogin.NewUser');

            $template->setSubject($this->l->t('New user created'));
            $template->addHeader();
            $template->addBodyText($this->l->t('User %s (%s) just created via social login', [$displayName, $uid]));
            $template->addFooter();

            $message = $this->mailer->createMessage();
            $message->setTo($sendTo);
            $message->useTemplate($template);
            try {
                $this->mailer->send($message);
            } catch (\Exception $e) {}
        }
    }
}
