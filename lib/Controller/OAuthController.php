<?php

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\ISession;
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


    public function __construct($appName, IRequest $request, IConfig $config, IURLGenerator $urlGenerator, SessionStorage $storage)
    {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->storage = $storage;
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
                'scope' => 'email',
            ];
        }
        $auth = new Hybridauth($config, null, $this->storage);
        $adapter = $auth->authenticate(ucfirst($provider));
        $profile = $adapter->getUserProfile();
        error_log(print_r($profile, true));
    }
}
