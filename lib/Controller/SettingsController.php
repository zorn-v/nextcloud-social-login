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

    public function saveAdmin($new_user_group, $providers)
    {
        $this->config->setAppValue($this->appName, 'new_user_group', $new_user_group);
        $this->config->setAppValue($this->appName, 'oauth_providers', json_encode($providers));
        return new JSONResponse(['success' => true]);
    }
}
