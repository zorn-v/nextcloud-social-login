<?php

namespace OCA\SocialLogin\Controller;

use OC\Authentication\Token\IProvider;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Authentication\Token\IToken;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCA\SocialLogin\Db\ConnectedLoginMapper;
use OCA\SocialLogin\Service\ProviderService;

class SettingsController extends Controller
{
    public function __construct(
        $appName,
        IRequest $request,
        private IAppConfig $appConfig,
        private IConfig $config,
        private IURLGenerator $urlGenerator,
        private IUserSession $userSession,
        private IL10N $l,
        private IProvider $tokenProvider,
        private ConnectedLoginMapper $socialConnect
    ) {
        parent::__construct($appName, $request);
    }

    public function saveAdmin($options, $providers, $custom_providers) {
        foreach ($options as $k => $v) {
            $this->appConfig->setValueBool($this->appName, $k, $v ? true : false);
        }

        if ($providers) {
            $this->appConfig->setValueArray($this->appName, 'oauth_providers', $providers, false, true);
        } else {
            $this->appConfig->deleteKey($this->appName, 'oauth_providers');
        }

        if (is_array($custom_providers)) {
            try {
                $names = ProviderService::DEFAULT_PROVIDERS;
                foreach ($custom_providers as $provType => $provs) {
                    $this->checkProviders($provs, $names);
                    $custom_providers[$provType] = array_values($provs);
                }
            } catch (\Exception $e) {
                return new JSONResponse(['message' => $e->getMessage()]);
            }
        }
        if ($custom_providers) {
            $this->appConfig->setValueArray($this->appName, 'custom_providers', $custom_providers, false, true);
        } else {
            $this->appConfig->deleteKey($this->appName, 'custom_providers');
        }

        return new JSONResponse(['success' => true]);
    }

    private function checkProviders($providers, &$names)
    {
        if (!is_array($providers)) {
            return;
        }
        foreach ($providers as $provider) {
            $name = $provider['name'];
            if (empty($name)) {
                throw new \Exception($this->l->t('Provider name cannot be empty'));
            }
            if (in_array($name, $names)) {
                throw new \Exception($this->l->t('Duplicate provider name "%s"', $name));
            }
            if (preg_match('#[^0-9a-z_.@-]#i', $name)) {
                throw new \Exception($this->l->t('Invalid provider name "%s". Allowed characters "0-9a-z_.@-"', $name));
            }
            $names[] = $name;
        }
    }

    /**
     * @NoAdminRequired
     * @PasswordConfirmationRequired
     */
    #[NoAdminRequired]
    #[PasswordConfirmationRequired]
    public function savePersonal($disable_password_confirmation)
    {
        $uid = $this->userSession->getUser()->getUID();
        $this->config->setUserValue($uid, $this->appName, 'disable_password_confirmation', $disable_password_confirmation ? 1 : 0);
        if (defined(IToken::class.'::SCOPE_SKIP_PASSWORD_VALIDATION')) {
            $token = $this->tokenProvider->getToken($this->userSession->getSession()->getId());
            $scope = $token->getScopeAsArray();
            $scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION] = (bool)$disable_password_confirmation;
            $token->setScope($scope);
            $this->tokenProvider->updateToken($token);
        }
        return new JSONResponse(['success' => true]);
    }

    /**
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function disconnectSocialLogin($login)
    {
        $this->socialConnect->disconnectLogin($login);
        return new RedirectResponse($this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section'=>'sociallogin']));
    }
}
