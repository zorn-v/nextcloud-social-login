<?php

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IURLGenerator;
use Hybridauth\Hybridauth;

class OAuthController extends Controller
{
    /** @var IConfig */
    private $config;
    /** @var IURLGenerator */
    private $urlGenerator;

    public function __construct($appName, IRequest $request, IConfig $config, IURLGenerator $urlGenerator)
    {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
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
        $auth = new Hybridauth($config);
        $adapter = $auth->authenticate(ucfirst($provider));
        $profile = $adapter->getUserProfile();
        error_log(print_r($profile, true));
    }
}
