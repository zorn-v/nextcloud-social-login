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
use OCA\SocialLogin\Provider\OpenID;
use Hybridauth\Hybridauth;
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
    public function oauth($provider)
    {
        $config = [
            'callback' => $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.oauth', ['provider'=>$provider])
        ];
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        foreach ($providers as $title=>$prov) {
            $idKey = in_array($title, ['twitter']) ? 'key' : 'id';
            $keys = [
                $idKey   => $prov['appid'],
                'secret' => $prov['secret'],
            ];
            $config['providers'][ucfirst($title)] = [
                'enabled' => true,
                'keys' => $keys,
                'scope' => 'email',
            ];
        }
        $auth = new Hybridauth($config, null, $this->storage);
        $adapter = $auth->authenticate(ucfirst($provider));
        $profile = $adapter->getUserProfile();
        $uid = $provider.'-'.$profile->identifier;

        return $this->login($uid, $profile);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function openid($provider)
    {
        $config = [
            'callback' => $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.openid', ['provider'=>$provider])
        ];
        $idUrl = null;
        $providers = json_decode($this->config->getAppValue($this->appName, 'openid_providers', '[]'), true);
        foreach ($providers as $prov) {
            if ($prov['title'] === $provider) {
                $idUrl = $prov['url'];
            }
        }
        if (!$idUrl) {
            throw new \InvalidArgumentException(sprintf('Unknown OpenID provider "%s"', $provider));
        }
        $config['openid_identifier'] = $idUrl;
        $adapter = new OpenID($config, null, $this->storage);
        $adapter->authenticate();
        $profile = $adapter->getUserProfile();
        $profileId = preg_replace('#.*/#', '', rtrim($profile->identifier, '/'));
        $uid = preg_replace('#[^0-9a-z_.@-]#i', '', $provider.'-'.$profileId);
        return $this->login($uid, $profile);
    }

    private function login($uid, Profile $profile)
    {
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
