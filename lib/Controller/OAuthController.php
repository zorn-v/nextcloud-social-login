<?php

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IURLGenerator;
use OCP\IAvatarManager;
use OCP\IGroupManager;
use OCA\SocialLogin\Storage\SessionStorage;
use Hybridauth\Hybridauth;
use Hybridauth\HttpClient\Curl;

class OAuthController extends Controller
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


    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        IURLGenerator $urlGenerator,
        SessionStorage $storage,
        IUserManager $userManager,
        IUserSession $userSession,
        IAvatarManager $avatarManager,
        IGroupManager $groupManager
    ) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->storage = $storage;
        $this->userManager = $userManager;
        $this->userSession = $userSession;
        $this->avatarManager = $avatarManager;
        $this->groupManager = $groupManager;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function login($provider)
    {
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        $config = [
            'callback' => $this->urlGenerator->linkToRouteAbsolute($this->appName.'.oAuth.login', ['provider'=>$provider])
        ];
        foreach ($providers as $title=>$prov) {
            if ($title === 'twitter') {
                $keys = [
                    'key' => $prov['appid'],
                    'secret' => $prov['secret'],
                ];
            } else {
                $keys = [
                    'id' => $prov['appid'],
                    'secret' => $prov['secret'],
                ];
            }
            $config['providers'][ucfirst($title)] = [
                'enabled' => true,
                'keys' => $keys,
                'scope' => 'email',
            ];
        }
        $providers = json_decode($this->config->getAppValue($this->appName, 'openid_providers', '[]'), true);
        foreach ($providers as $title) {
            $config['providers'][ucfirst($title)] = ['enabled' => true];
        }
        $auth = new Hybridauth($config, null, $this->storage);
        $adapter = $auth->authenticate(ucfirst($provider));
        $profile = $adapter->getUserProfile();
        $uid = $provider.'-'.$profile->identifier;
        if (null === $this->userManager->get($uid)) {
            $password = substr(base64_encode(random_bytes(64)), 0, 10);
            $user = $this->userManager->createUser($uid, $password);
            $this->config->setUserValue($uid, $this->appName, 'password', $password);
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
        } else {
            $password = $this->config->getUserValue($uid, $this->appName, 'password');
        }
        $this->userSession->login($uid, $password);
        $this->userSession->createSessionToken($this->request, $uid, $uid, $password);
        return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
    }
}
