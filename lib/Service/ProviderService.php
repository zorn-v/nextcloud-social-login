<?php

namespace OCA\SocialLogin\Service;

use Hybridauth\Provider;
use Hybridauth\User\Profile;
use Hybridauth\HttpClient\Curl;
use OC\User\LoginException;
use OCA\SocialLogin\Provider\CustomOAuth1;
use OCA\SocialLogin\Provider\CustomOAuth2;
use OCA\SocialLogin\Provider\CustomOpenIDConnect;
use OCA\SocialLogin\Db\SocialConnectDAO;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Util;

class ProviderService
{
    const OPTIONS = [
        'disable_registration',
        'create_disabled_users',
        'allow_login_connect',
        'prevent_create_email_exists',
        'update_profile_on_login',
        'no_prune_user_groups',
        'auto_create_groups',
        'restrict_users_wo_mapped_groups',
        'disable_notify_admins',
    ];
    const DEFAULT_PROVIDERS = [
        'google',
        'amazon',
        'facebook',
        'twitter',
        'GitHub',
        'discord',
        'QQ',
        'slack',
        'telegram',
    ];

    const TYPE_OPENID = 'openid';
    const TYPE_OAUTH1 = 'custom_oauth1';
    const TYPE_OAUTH2 = 'custom_oauth2';
    const TYPE_OIDC = 'custom_oidc';

    const TYPE_CLASSES = [
        self::TYPE_OPENID => Provider\OpenID::class,
        self::TYPE_OAUTH1 => CustomOAuth1::class,
        self::TYPE_OAUTH2 => CustomOAuth2::class,
        self::TYPE_OIDC => CustomOpenIDConnect::class,
    ];

    private $configMapping = [
        'default' => [
            'keys' => [
                'id' => 'appid',
                'secret' => 'secret',
            ],
        ],
        self::TYPE_OPENID => [
            'openid_identifier' => 'url',
        ],
        self::TYPE_OAUTH1 => [
            'keys' => [
                'id'     => 'clientId',
                'secret' => 'clientSecret',
            ],
            'endpoints' => [
                'authorize_url'    => 'authorizeUrl',
                'access_token_url' => 'tokenUrl',
                'profile_url'    => 'profileUrl',
            ],
            'logout_url' => 'logoutUrl',
        ],
        self::TYPE_OAUTH2 => [
            'scope' => 'scope',
            'keys' => [
                'id'     => 'clientId',
                'secret' => 'clientSecret',
            ],
            'endpoints' => [
                'api_base_url'     => 'apiBaseUrl',
                'authorize_url'    => 'authorizeUrl',
                'access_token_url' => 'tokenUrl',
                'profile_url'    => 'profileUrl',
            ],
            'profile_fields' => 'profileFields',
            'groups_claim'  => 'groupsClaim',
            'group_mapping' => 'groupMapping',
            'logout_url'    => 'logoutUrl',
        ],
        self::TYPE_OIDC => [
            'scope' => 'scope',
            'keys' => [
                'id'     => 'clientId',
                'secret' => 'clientSecret',
            ],
            'endpoints' => [
                'authorize_url'    => 'authorizeUrl',
                'access_token_url' => 'tokenUrl',
                'user_info_url'    => 'userInfoUrl',
            ],
            'groups_claim'  => 'groupsClaim',
            'group_mapping' => 'groupMapping',
            'logout_url'    => 'logoutUrl',
        ],
    ];

    /** @var string */
    private $appName;
    /** @var IRequest */
    private $request;
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
        $this->appName = $appName;
        $this->request = $request;
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

    public function getAuthUrl($name, $appId)
    {
        $redirectUrl = $this->request->getParam('redirect_url');
        $authUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.oauth', [
            'provider' => $name,
            'login_redirect_url' => $redirectUrl
        ]);
        switch ($name) {
            case 'telegram':
                $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
                $csp->addAllowedScriptDomain('telegram.org')
                    ->addAllowedFrameDomain('oauth.telegram.org')
                ;
                $manager = \OC::$server->getContentSecurityPolicyManager();
                $manager->addDefaultPolicy($csp);
                Util::addHeader('meta', [
                    'id' => 'tg-data',
                    'data-login' => $appId,
                    'data-redirect-url' => $authUrl,
                ]);
                Util::addScript($this->appName, 'telegram');
                return false;
        }

        return $authUrl;
    }

    public function handleDefault($provider)
    {
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers'), true) ?: [];
        if (is_array($providers) && in_array($provider, array_keys($providers))) {
            foreach ($providers as $name => $prov) {
                if ($name === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.oauth', ['provider' => $provider]);
                    $config = array_merge([
                        'callback' => $callbackUrl,
                        'default_group' => $prov['defaultGroup'],
                    ], $this->applyConfigMapping('default', $prov));

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

    public function handleCustom($type, $provider)
    {
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true) ?: [];
        if (isset($providers[$type])) {
            foreach ($providers[$type] as $prov) {
                if ($prov['name'] === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.custom', [
                        'type'=> $type,
                        'provider' => $provider
                    ]);
                    $config = array_merge([
                        'callback'          => $callbackUrl,
                        'default_group'     => $prov['defaultGroup'],
                    ], $this->applyConfigMapping($type, $prov));

                    if (isset($config['endpoints']['authorize_url']) && strpos($config['endpoints']['authorize_url'], '?') !== false) {
                        list($authUrl, $authQuery) = explode('?', $config['endpoints']['authorize_url'], 2);
                        $config['endpoints']['authorize_url'] = $authUrl;
                        parse_str($authQuery, $config['authorize_url_parameters']);
                    }
                    break;
                }
            }
        }
        return $this->auth(self::TYPE_CLASSES[$type], $config, $provider);
    }

    private function applyConfigMapping($mapping, $data)
    {
        if (!is_array($mapping)) {
            if (!isset($this->configMapping[$mapping])) {
                throw new LoginException(sprintf('Unknown provider type: %s', $mapping));
            }
            $mapping = $this->configMapping[$mapping];
        }
        $result = [];
        foreach ($mapping as $k => $v) {
            if (is_array($v)) {
                $result[$k] = $this->applyConfigMapping($v, $data);
            } else {
                $result[$k] = isset($data[$v]) ? $data[$v] : null;
            }
        }
        return $result;
    }

    private function auth($class, array $config, $provider, $providerType = null)
    {
        if (empty($config)) {
            if (!$providerType) {
                $providerType = explode('\\', $class);
                $providerType = end($providerType);
            }
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
            $this->storage->clear();
            throw new LoginException($e->getMessage());
        }
        $profileId = preg_replace('#.*/#', '', rtrim($profile->identifier, '/'));
        if (empty($profileId)) {
            $this->storage->clear();
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

        if ($provider === 'telegram') {
            $provider = 'tg'; //For backward compatibility
        }
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
