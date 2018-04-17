<?php

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;

class SettingsController extends Controller
{
    private $config;

    public function __construct($appName, IRequest $request, IConfig $config)
    {
        parent::__construct($appName, $request);
        $this->config = $config;
    }

    public function saveAdmin($new_user_group, $disable_registration, $allow_login_connect, $providers, $openid_providers)
    {
        $this->config->setAppValue($this->appName, 'new_user_group', $new_user_group);
        $this->config->setAppValue($this->appName, 'disable_registration', $disable_registration ? true : false);
        $this->config->setAppValue($this->appName, 'allow_login_connect', $allow_login_connect ? true : false);
        $this->config->setAppValue($this->appName, 'oauth_providers', json_encode($providers));
        $this->config->setAppValue($this->appName, 'openid_providers', json_encode(array_values($openid_providers)));
        return new JSONResponse(['success' => true]);
    }
}
