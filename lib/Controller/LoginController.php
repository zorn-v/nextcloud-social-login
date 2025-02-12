<?php

namespace OCA\SocialLogin\Controller;

use OC\User\LoginException;
use OCA\SocialLogin\Service\ProviderService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IURLGenerator;
use OCP\IRequest;

class LoginController extends Controller
{
    /** @var ProviderService */
    private $providerService;
    /** @var IURLGenerator */
    private $urlGenerator;

    public function __construct($appName, IRequest $request, ProviderService $providerService,  IURLGenerator $urlGenerator)
    {
        parent::__construct($appName, $request);
        $this->providerService = $providerService;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[UseSession]
    public function oauth($provider)
    {
        if ($this->request->getMethod() === 'POST') {
            return $this->postRedirect();
        }
        return $this->providerService->handleDefault($provider);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[UseSession]
    public function custom($type, $provider)
    {
        if (!isset(ProviderService::TYPE_CLASSES[$type])) {
            throw new LoginException(sprintf('Unknown provider type: %s', $type));
        }
        if ($this->request->getMethod() === 'POST') {
            return $this->postRedirect();
        }
        return $this->providerService->handleCustom($type, $provider);
    }

    private function postRedirect()
    {
        $params = $this->request->getParams();
        $routeName = $params['_route'];
        unset($params['_route']);
        return new RedirectResponse($this->urlGenerator->linkToRoute($routeName, $params));
    }
}
