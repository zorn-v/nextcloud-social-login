<?php

namespace OCA\SocialLogin\Service;

use Hybridauth\Provider;
use Hybridauth\User\Profile;
use Hybridauth\HttpClient\Curl;
use OC\Authentication\Token\IProvider;
use OC\User\LoginException;
use OCA\SocialLogin\AlternativeLogin;
use OCA\SocialLogin\AlternativeLogin\SocialLogin;
use OCA\SocialLogin\Provider\CustomDiscourse;
use OCA\SocialLogin\Provider\CustomOAuth1;
use OCA\SocialLogin\Provider\CustomOAuth2;
use OCA\SocialLogin\Provider\CustomOpenIDConnect;
use OCA\SocialLogin\Db\ConnectedLoginMapper;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Authentication\Token\IToken;
use OCP\IAppConfig;
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
        'button_text_wo_prefix',
    ];
    const DEFAULT_PROVIDERS = [
        'apple',
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
        'yandex',
        'BitBucket',
        'PlexTv',
    ];

    const TYPE_OPENID = 'openid';
    const TYPE_OAUTH1 = 'custom_oauth1';
    const TYPE_OAUTH2 = 'custom_oauth2';
    const TYPE_OIDC = 'custom_oidc';
    const TYPE_DISCOURSE = 'custom_discourse';

    const TYPE_CLASSES = [
        self::TYPE_OPENID => Provider\OpenID::class,
        self::TYPE_OAUTH1 => CustomOAuth1::class,
        self::TYPE_OAUTH2 => CustomOAuth2::class,
        self::TYPE_OIDC => CustomOpenIDConnect::class,
        self::TYPE_DISCOURSE => CustomDiscourse::class,
    ];

    private $configMapping = [
        'default' => [
            'keys' => [
                'id' => 'appid',
                'secret' => 'secret',
                // Apple below
                'team_id' => 'teamId',
                'key_id' => 'keyId',
                'key_content' => 'keyContent',
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
            'identifier_claim' => 'identifierClaim',
            'displayname_claim' => 'displayNameClaim',
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
            'identifier_claim' => 'identifierClaim',
            'displayname_claim' => 'displayNameClaim',
            'groups_claim'  => 'groupsClaim',
            'group_mapping' => 'groupMapping',
            'logout_url'    => 'logoutUrl',
        ],
        self::TYPE_DISCOURSE => [
            'keys' => [
                'secret' => 'ssoSecret',
            ],
            'endpoints' => [
                'base_url'    => 'baseUrl',
            ],
            'group_mapping' => 'groupMapping',
            'logout_url'    => 'logoutUrl',
        ],
    ];


    public function __construct(
        private $appName,
        private IRequest $request,
        private IConfig $config,
        private IAppConfig $appConfig,
        private IURLGenerator $urlGenerator,
        private SessionStorage $storage,
        private IUserManager $userManager,
        private IUserSession $userSession,
        private IAvatarManager $avatarManager,
        private IGroupManager $groupManager,
        private ISession $session,
        private IL10N $l,
        private IMailer $mailer,
        private ConnectedLoginMapper $socialConnect,
        private IAccountManager $accountManager,
        private IProvider $tokenProvider
    ) {}

    public function getLoginClass($name, $provider = [], $type = null)
    {
        $redirectUrl = $this->request->getParam('redirect_url');
        $routeName =  $this->appName.'.login.'.($type ? 'custom' : 'oauth');
        $authUrl = $this->urlGenerator->linkToRouteAbsolute($routeName, [
            'type' => $type,
            'provider' => $name,
            'login_redirect_url' => $redirectUrl
        ]);
        $className = sprintf('%s\%sLogin', AlternativeLogin::class, ucfirst($name));
        $class = class_exists($className) ? $className : SocialLogin::class;
        if (method_exists($class, 'addLogin')) {
            $title = $provider['title'] ?? ucfirst($name);
            $label = $this->appConfig->getValueBool($this->appName, 'button_text_wo_prefix')
                ? $title
                : $this->l->t('Log in with %s', $title);
            $class::addLogin($label, $authUrl, $provider['style'] ?? '');
        }
        return $class;
    }

    public function handleDefault($provider)
    {
        $config = [];
        $scopes = [
            'discord' => 'identify email guilds guilds.members.read',
        ];
        $providers = $this->appConfig->getValueArray($this->appName, 'oauth_providers');
        if (isset($providers[$provider])) {
            $prov = $providers[$provider];

            $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.oauth', ['provider' => $provider]);
            $config = array_merge([
                'callback' => $callbackUrl,
                'default_group' => $prov['defaultGroup'],
            ], $this->applyConfigMapping('default', $prov));
            $opts = ['orgs', 'workspace', 'guilds', 'groupMapping', 'useGuildNames'];
            foreach ($opts as $opt) {
                if (isset($prov[$opt])) {
                    $config[$opt] = $prov[$opt];
                }
            }

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
        }
        return $this->auth(Provider::class.'\\'.ucfirst($provider), $config, $provider, 'OAuth');
    }

    public function handleCustom($type, $provider)
    {
        $config = [];
        $providers = $this->appConfig->getValueArray($this->appName, 'custom_providers');
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

        $curlOptions = [];
        $httpClientConfig = $this->config->getSystemValue('social_login_http_client', []);
        if (isset($httpClientConfig['timeout'])) {
            $curlOptions[CURLOPT_TIMEOUT] = $httpClientConfig['timeout'];
            $curlOptions[CURLOPT_CONNECTTIMEOUT] = $httpClientConfig['timeout'];
        }
        if (isset($httpClientConfig['proxy'])) {
            $curlOptions[CURLOPT_PROXY] = $httpClientConfig['proxy'];
        }
        if ($curlOptions) {
            $config['curl_options'] = $curlOptions;
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

        if ($provider === 'BitBucket') {
            if (empty($config['workspace'])) {
                throw new LoginException($this->l->t('Invalid setup for Bitbucket workspaces', $config['workspace']));
            }
            $allowedWorks = array_map('trim', explode(',', $config['workspace']));
            $username = $adapter->apiRequest('user')->login;
            $checkWorks = function () use ($adapter, $allowedWorks, $config) {
                try {
                    $workspaceData = $adapter->apiRequest('workspaces');
                    $workspaces = array_map(function ($w) {
                        return $w->slug;
                    }, $workspaceData->values);
                    $workspaces = array_intersect($workspaces, $allowedWorks);
                    if (count($workspaces) > 0) {
                        return;
                    }
                } catch (\Exception $e) {}
                $this->storage->clear();
                throw new LoginException($this->l->t('Login is available only to members of the following Bitbucket workspaces: %s', $config['workspace']));
            };
            $checkWorks();
        }

        if ($provider === 'discord' && !empty($config['guilds'])) {
            $allowedGuilds = array_map('trim', explode(',', $config['guilds']));
            $userGuilds = $adapter->apiRequest('users/@me/guilds');
            $checkGuilds = function () use ($allowedGuilds, $userGuilds, $config) {
                foreach ($userGuilds as $guild) {
                    if (in_array($guild->id ?? null, $allowedGuilds)) {
                        return $guild->id;
                    }
                }
                $this->storage->clear();
                throw new LoginException($this->l->t('Login is available only to members of the following Discord guilds: %s', $config['guilds']));
            };
            $matchingGuildId = $checkGuilds();

            // Use discord guild member nickname as display name
            if (!empty($config['useGuildNames']) && $matchingGuildId) {
                $guildMember = $adapter->apiRequest('users/@me/guilds/' . $matchingGuildId . '/member' );
                $profile->displayName = $guildMember->nick ?? $profile->displayName;
            }

            if ($allowedGuilds && !empty($config['groupMapping'])) {
                // read Discord roles into NextCloud groups
                $profile->data['groups'] = [];
                $profile->data['group_mapping'] = $config['groupMapping'];
                foreach($userGuilds as $guild) {
                    if (empty($guild->id) || !in_array($guild->id, $allowedGuilds)) {
                        // Only read groups from the explicitly declared guilds.
                        // It doesn't make sense to try to map in random, unknown groups from arbitrary guilds.
                        // and without this, a user in many guilds will trip a HTTP 429 rate limit from the Discord API.
                        continue;
                    }
                    # https://discord.com/developers/docs/resources/guild#get-guild-member
                    $guildMember = $adapter->apiRequest('users/@me/guilds/' . $guild->id . '/member' );
                    $profile->data['groups'] = array_merge($profile->data['groups'], $guildMember->roles ?? []);
                    // TODO: /member returns roles as their ID; to get their name requires an extra API call
                    //       (and perhaps extra permissions?)
                }
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
            if (!$this->appConfig->getValueBool($this->appName, 'allow_login_connect')) {
                throw new LoginException($this->l->t('Social login connect is disabled'));
            }
            if (null !== $user) {
                throw new LoginException($this->l->t('This account already connected'));
            }
            $currentUid = $this->userSession->getUser()->getUID();
            $this->socialConnect->connectLogin($currentUid, $uid);
            return new RedirectResponse($this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section'=>'sociallogin']));
        }

        if ($this->appConfig->getValueBool($this->appName, 'restrict_users_wo_assigned_groups') && empty($profile->data['groups'])) {
            throw new LoginException($this->l->t('Users without assigned groups is not allowed to login, please contact support'));
        }

        if ($this->appConfig->getValueBool($this->appName, 'restrict_users_wo_mapped_groups') && isset($profile->data['group_mapping'])) {
            $groups = isset($profile->data['groups']) ? $profile->data['groups'] : [];
            $mappedGroups = array_intersect($groups, array_keys($profile->data['group_mapping']));
            if (!$mappedGroups) {
                throw new LoginException($this->l->t('Your user group is not allowed to login, please contact support'));
            }
        }

        $updateUserProfile = $this->appConfig->getValueBool($this->appName, 'update_profile_on_login');
        $userPassword = '';

        if (null === $user) {
            if ($this->appConfig->getValueBool($this->appName, 'disable_registration')) {
                throw new LoginException($this->l->t('Auto creating new users is disabled'));
            }
            if (
                $profile->email && $this->appConfig->getValueBool($this->appName, 'prevent_create_email_exists')
                && count($this->userManager->getByEmail($profile->email)) !== 0
            ) {
                throw new LoginException($this->l->t('Email already registered'));
            }
            $userPassword = '1@aA'.substr(base64_encode(random_bytes(64)), 0, 30);
            $user = $this->userManager->createUser($uid, $userPassword);

            if ($this->appConfig->getValueBool($this->appName, 'create_disabled_users')) {
                $user->setEnabled(false);
            }

            $this->config->setUserValue($uid, $this->appName, 'disable_password_confirmation', 1);
            $updateUserProfile = true;

            if (!$this->appConfig->getValueBool($this->appName, 'disable_notify_admins')) {
                $this->notifyAdmins($uid, $profile->displayName ?: $profile->identifier, $profile->data['default_group']);
            }
        }

        if ($updateUserProfile) {
            $user->setDisplayName($profile->displayName ?: $profile->identifier);
            $user->setSystemEMailAddress((string)$profile->email);

            if ($profile->photoURL) {
                $curl = new Curl();
                try {
                    $photo = $curl->request($profile->photoURL);
                    $avatar = $this->avatarManager->getAvatar($user->getUid());
                    $avatar->set($photo);
                } catch (\Throwable $e) {}
            }

            if (isset($profile->data['groups']) && is_array($profile->data['groups'])) {
                $groups = $profile->data['groups'];
                $groupMapping = $profile->data['group_mapping'] ?? null;
                $userGroups = $this->groupManager->getUserGroups($user);
                $autoCreateGroups = $this->appConfig->getValueBool($this->appName, 'auto_create_groups');
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

                if (!$this->appConfig->getValueBool($this->appName, 'no_prune_user_groups')) {
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

            $updateAccount = false;
            $account = $this->accountManager->getAccount($user);
            if (isset($profile->address)) {
                $updateAccount = true;
                $account->setProperty(IAccountManager::PROPERTY_ADDRESS, $profile->address, IAccountManager::SCOPE_PRIVATE, IAccountManager::NOT_VERIFIED);
            }
            if (isset($profile->phone)) {
                $updateAccount = true;
                $account->setProperty(IAccountManager::PROPERTY_PHONE, $profile->phone, IAccountManager::SCOPE_PRIVATE, IAccountManager::NOT_VERIFIED);
            }
            if (isset($profile->webSiteURL)) {
                $updateAccount = true;
                $account->setProperty(IAccountManager::PROPERTY_WEBSITE, $profile->webSiteURL, IAccountManager::SCOPE_PRIVATE, IAccountManager::NOT_VERIFIED);
            }
            if ($updateAccount) {
                $this->accountManager->updateAccount($account);
            }

            $defaultGroup = $profile->data['default_group'];
            if ($defaultGroup && ($group = $this->groupManager->get($defaultGroup)) && !$group->inGroup($user)) {
                $group->addUser($user);
            }
        }

        $this->userSession->getSession()->regenerateId();
        $this->userSession->setTokenProvider($this->tokenProvider);
        $this->userSession->createSessionToken($this->request, $user->getUID(), $user->getUID());
        $this->userSession->createRememberMeToken($user);

        $token = $this->tokenProvider->getToken($this->userSession->getSession()->getId());
        // needed since NC 30.0.3
        if (
            $this->config->getUserValue($user->getUid(), $this->appName, 'disable_password_confirmation')
            && defined(IToken::class.'::SCOPE_SKIP_PASSWORD_VALIDATION')
        ) {
            $scope = $token->getScopeAsArray();
            $scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION] = true;
            $token->setScope($scope);
            $this->tokenProvider->updateToken($token);
        }
        $this->userSession->completeLogin($user, [
            'loginName' => $user->getUID(),
            'password' => $userPassword,
            'token' => $userPassword ? null : $token,
        ], false);

        $user->updateLastLoginTimestamp();

        //Workaround to create user files folder. Remove it later.
        \OC::$server->get(\OCP\Files\IRootFolder::class)->getUserFolder($user->getUID());

        if ($redirectUrl = $this->session->get('login_redirect_url')) {
            if (strpos($redirectUrl, '/') === 0) {
                // URL relative to the Nextcloud webroot, generate an absolute one
                $redirectUrl = $this->urlGenerator->getAbsoluteURL($redirectUrl);
            } // else, this is an absolute URL, leave it as-is

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
