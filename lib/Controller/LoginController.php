<?php

namespace OCA\SocialLogin\Controller;

use OCA\SocialLogin\Service\ProviderService;
use OCP\AppFramework\Controller;
use OCP\IRequest;

class LoginController extends Controller
{
    /** @var ProviderService */
    private $providerService;

    public function __construct($appName, IRequest $request, ProviderService $providerService)
    {
        parent::__construct($appName, $request);
        $this->providerService = $providerService;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function oauth($provider)
    {
        return $this->providerService->oauth($provider);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function openid($provider)
    {
        return $this->providerService->openid($provider);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function customOidc($provider)
    {
        return $this->providerService->customOidc($provider);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function customOauth2($provider)
    {
        return $this->providerService->customOauth2($provider);
    }
}
