<?php

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IURLGenerator;
use OCA\SocialLogin\Storage\SessionStorage;
use Hybridauth\Hybridauth;

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


    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        IURLGenerator $urlGenerator,
        SessionStorage $storage,
        IUserManager $userManager,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->storage = $storage;
        $this->userManager = $userManager;
        $this->userSession = $userSession;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function login($provider)
    {
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers'), true);
        $config = [
            'callback' => $this->urlGenerator->linkToRouteAbsolute($this->appName.'.oAuth.login', ['provider'=>$provider])
        ];
        foreach ($providers as $title=>$prov) {
            $keys = [
                'id' => $prov['appid'],
                'secret' => $prov['secret'],
            ];
            $config['providers'][ucfirst($title)] = [
                'enabled' => true,
                'keys' => $keys,
                'scope' => '',
            ];
        }
        $auth = new Hybridauth($config, null, $this->storage);
        $adapter = $auth->authenticate(ucfirst($provider));
        $profile = $adapter->getUserProfile();
        $uid = $provider.'-'.$profile->identifier;
        if (null === $user = $this->userManager->get($uid)) {
            $password = substr(base64_encode(random_bytes(64)), 0, 10);
            $user = $this->userManager->createUser($uid, $password);
            $user->setDisplayName($profile->displayName);
            $this->userSession->login($uid, $password);
        }
        return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
    }
}
