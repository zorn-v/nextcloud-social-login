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
use OC\User\LoginException;
use OCA\SocialLogin\Storage\SessionStorage;
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
        $this->l = $l;
        $this->socialConnect = $socialConnect;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function oauth($provider)
    {
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
                    if ('Facebook' === ucfirst($provider)) {
                        $config['scope'] = 'public_profile, email'; // The default scope of Hybridauth requires app review from Facebook
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
                            'api_base_url'     => '',
                        ]),
                    ];
                    break;
                }
            }
        }
        return $this->auth(CustomOpenIDConnect::class, $config, $provider, 'OpenID Connect');
    }

    private function auth($class, array $config, $provider, $providerTitle)
    {
        if (empty($config)) {
            throw new LoginException($this->l->t('Unknown %s provider: "%s"', [$providerTitle, $provider]));
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
            $password = substr(base64_encode(random_bytes(64)), 0, 30);
            $user = $this->userManager->createUser($uid, $password);
            $user->setDisplayName((string)$profile->displayName);
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
                $photo = $curl->request($profile->photoURL);
                try {
                    $avatar = $this->avatarManager->getAvatar($uid);
                    $avatar->set($photo);
                } catch (\Exception $e) {}
            }
        }

        $this->userSession->completeLogin($user, ['loginName' => $user->getUID(), 'password' => null], false);
        $this->userSession->createSessionToken($this->request, $user->getUID(), $user->getUID());

        return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
    }
}
