<?php

namespace OCA\SocialLogin\Service;

use Hybridauth\Provider;
use Hybridauth\User\Profile;
use Hybridauth\HttpClient\Curl;
use OC\Authentication\Token\DefaultTokenProvider;
use OC\User\LoginException;
use OCA\SocialLogin\Provider\CustomOAuth1;
use OCA\SocialLogin\Provider\CustomOAuth2;
use OCA\SocialLogin\Provider\CustomOpenIDConnect;
use OCA\SocialLogin\Db\ConnectedLoginMapper;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\EventDispatcher\IEventDispatcher;
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
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
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
        'restrict_users_wo_assigned_groups',
        'disable_notify_admins',
        'hide_default_login',
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
        'mailru',
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
    /** @var ConnectedLoginMapper */
    private $socialConnect;
    /** @var IAccountManager */
    private $accountManager;
    /** @var IEventDispatcher */
    private $dispatcher;
    /** @var DefaultTokenProvider */
    private $tokenProvider;
    /** @var AdapterService  */
    private $adapterService;
    /** @var ConfigService  */
    private $configService;
    /** @var TokenService */
    private $tokenService;

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
        ConnectedLoginMapper $socialConnect,
        IAccountManager $accountManager,
        IEventDispatcher $dispatcher,
        DefaultTokenProvider $tokenProvider,
        AdapterService $adapterService,
        ConfigService $configService,
        TokenService $tokenService
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
        $this->accountManager = $accountManager;
        $this->dispatcher = $dispatcher;
        $this->tokenProvider = $tokenProvider;
        $this->adapterService = $adapterService;
        $this->configService = $configService;
        $this->tokenService = $tokenService;
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
                $this->dispatcher->addListener(AddContentSecurityPolicyEvent::class, function ($event) {
                    $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
                    $csp->addAllowedScriptDomain('telegram.org')
                        ->addAllowedFrameDomain('oauth.telegram.org')
                    ;
                    $event->addPolicy($csp);
                });
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
        $config = $this->configService->defaultConfig($provider);

        return $this->auth(Provider::class.'\\'.ucfirst($provider), $config, $provider, 'OAuth');
    }

    public function handleCustom($type, $provider)
    {
        $config = $this->configService->customConfig($type, $provider);

        return $this->auth(ConfigService::TYPE_CLASSES[$type], $config, $provider, $type);
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

        $curlOptions = [];
        $httpClientConfig = $this->config->getSystemValue('social_login_http_client', []);
        if (isset($httpClientConfig['timeout'])) {
            $curlOptions[CURLOPT_TIMEOUT] = $httpClientConfig['timeout'];
            $curlOptions[CURLOPT_CONNECTTIMEOUT] = $httpClientConfig['timeout'];
        }
        if ($curlOptions) {
            $config['curl_options'] = $curlOptions;
        }

        try {
            $adapter = $this->adapterService->new($class, $config, $this->storage);
            if (array_key_exists('saveTokens', $config) && $config['saveTokens'] == true ) {
                $this->tokenService->authenticate($adapter, $providerType, $provider);
            } else{
                $adapter->authenticate();
            }
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

        if ($provider === 'GitHub' && !empty($config['orgs'])) {
            $allowedOrgs = array_map('trim', explode(',', $config['orgs']));
            $username = $adapter->apiRequest('user')->login;
            $checkOrgs = function () use ($adapter, $allowedOrgs, $username, $config) {
                foreach ($allowedOrgs as $org) {
                    try {
                        $adapter->apiRequest('orgs/'.$org.'/members/'.$username);
                        return;
                    } catch (\Exception $e) {}
                }
                $this->storage->clear();
                throw new LoginException($this->l->t('Login is available only to members of the following GitHub organizations: %s', $config['orgs']));
            };
            $checkOrgs();
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

        if ($this->config->getAppValue($this->appName, 'restrict_users_wo_assigned_groups') && empty($profile->data['groups'])) {
            throw new LoginException($this->l->t('Users without assigned groups is not allowed to login, please contact support'));
        }

        if ($this->config->getAppValue($this->appName, 'restrict_users_wo_mapped_groups') && isset($profile->data['group_mapping'])) {
            $groups = isset($profile->data['groups']) ? $profile->data['groups'] : [];
            $mappedGroups = array_intersect($groups, array_keys($profile->data['group_mapping']));
            if (!$mappedGroups) {
                throw new LoginException($this->l->t('Your user group is not allowed to login, please contact support'));
            }
        }

        $updateUserProfile = $this->config->getAppValue($this->appName, 'update_profile_on_login');
        $userPassword = '';

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
            $userPassword = substr(base64_encode(random_bytes(64)), 0, 30);
            $user = $this->userManager->createUser($uid, $userPassword);

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
                    $avatar = $this->avatarManager->getAvatar($user->getUid());
                    $avatar->set($photo);
                } catch (\Exception $e) {}
            }

            if (isset($profile->data['groups']) && is_array($profile->data['groups'])) {
                $groups = $profile->data['groups'];
                $groupMapping = isset($profile->data['group_mapping']) ? $profile->data['group_mapping'] : null;
                $userGroups = $this->groupManager->getUserGroups($user);
                $autoCreateGroups = $this->config->getAppValue($this->appName, 'auto_create_groups');
                $syncGroups = [];

                foreach ($groups as $k => $v) {
                    if (is_object($v)) {
                        if (empty($v->gid) && $v->gid !== '0' && $v->gid !== 0) {
                            continue;
                        }
                        $group = $v;
                    } else {
                        $group = (object) array('gid' => $v);
                    }

                    if ($groupMapping && isset($groupMapping[$group->gid])) {
                        $syncGroups[] = (object) array('gid' => $groupMapping[$group->gid]);
                    }
                    $autoGroup = $newGroupPrefix.$group->gid;
                    $group->gid = $autoGroup;
                    if ($autoCreateGroups || $this->groupManager->groupExists($group->gid)) {
                        $syncGroups[] = $group;
                    }
                }

                if (!$this->config->getAppValue($this->appName, 'no_prune_user_groups')) {
                    foreach ($userGroups as $group) {
                        if (!in_array($group->getGID(), array_column($syncGroups, 'gid'))) {
                            $group->removeUser($user);
                        }
                    }
                }

                foreach ($syncGroups as $group) {
                    if ($newGroup = $this->groupManager->createGroup($group->gid)) {
                        $newGroup->addUser($user);

                        if(isset($group->displayName)) {
                            $newGroup->setDisplayName($group->displayName);
                        }
                    }
                }

            }

            if (isset($profile->address)) {
                $account = $this->accountManager->getUser($user);
                $account['address']['value'] = $profile->address;
                $this->accountManager->updateUser($user, $account);
            }

            $defaultGroup = $profile->data['default_group'];
            if ($defaultGroup && $group = $this->groupManager->get($defaultGroup)) {
                $group->addUser($user);
            }
        }

        $this->userSession->getSession()->regenerateId();
        $this->userSession->setTokenProvider($this->tokenProvider);
        $this->userSession->createSessionToken($this->request, $user->getUID(), $user->getUID());

        $token = $this->tokenProvider->getToken($this->userSession->getSession()->getId());
        $this->userSession->completeLogin($user, [
            'loginName' => $user->getUID(),
            'password' => $userPassword,
            'token' => $userPassword ? null : $token,
        ], false);

        //Workaround to create user files folder. Remove it later.
        \OC::$server->get(\OCP\Files\IRootFolder::class)->getUserFolder($user->getUID());

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
