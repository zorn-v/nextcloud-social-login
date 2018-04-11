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

    public function saveAdmin(
        $new_user_group,
        $facebook_appid,
        $facebook_secret,
        $google_appid,
        $google_secret
    ) {
        $r = new \ReflectionMethod(__METHOD__);
        $names = $r->getParameters();
        $values = func_get_args();
        foreach ($names as $k=>$name) {
            $this->config->setAppValue($this->appName, $name->name, $values[$k]);
        }
        return new JSONResponse(['success' => true]);
    }
}
