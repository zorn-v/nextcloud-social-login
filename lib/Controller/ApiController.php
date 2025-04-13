<?php

declare(strict_types=1);

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\ApiController as BaseController;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Response;
use OCP\IAppConfig;
use OCP\IRequest;

class ApiController extends BaseController
{
    public function __construct(
        $appName,
        IRequest $request,
        private IAppConfig $appConfig
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoCSRFRequired
     */
    #[NoCSRFRequired]
    public function setConfig($key, $config)
    {
        $arrayKeys = ['oauth_providers', 'custom_providers'];
        if (in_array($key, $arrayKeys)) {
            $this->appConfig->setValueArray($this->appName, $key, json_decode($config, true, flags: JSON_THROW_ON_ERROR), false, true);
        } else {
            $this->appConfig->setValueBool($this->appName, $key, (bool)$config);
        }
        return new Response();
    }
}
