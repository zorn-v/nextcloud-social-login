<?php

namespace OCA\SocialLogin\Settings;

use OCA\SocialLogin\Db\ConnectedLoginMapper;
use OCA\SocialLogin\Service\ProviderService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;
use Psr\Container\ContainerInterface;

class PersonalSettings implements ISettings
{
    public function __construct(
        private $appName,
        private IConfig $config,
        private IAppConfig $appConfig,
        private IURLGenerator $urlGenerator,
        private IUserSession $userSession,
        private ConnectedLoginMapper $socialConnect,
        private ProviderService $providerService,
        private ContainerInterface $container
    ) {}

    public function getForm()
    {
        Util::addScript($this->appName, 'personal');
        $uid = $this->userSession->getUser()->getUID();
        $params = [
            'providers' => [],
            'connected_logins' => [],
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.savePersonal'),
            'allow_login_connect' => $this->appConfig->getValueBool($this->appName, 'allow_login_connect'),
            'disable_password_confirmation' => $this->config->getUserValue($uid, $this->appName, 'disable_password_confirmation'),
        ];
        if ($params['allow_login_connect']) {
            $providers = $this->appConfig->getValueArray($this->appName, 'oauth_providers');
            foreach ($providers as $name => $provider) {
                if ($provider['appid']) {
                    $class = $this->providerService->getLoginClass($name);
                    $login = $this->container->get($class);
                    $login->load();
                    $params['providers'][ucfirst($name)] = ['url' => $login->getLink(), 'style' => $login->getClass()];
                }
            }
            $params['providers'] = array_merge($params['providers'], $this->getCustomProviders());

            $connectedLogins = $this->socialConnect->getConnectedLogins($uid);
            foreach ($connectedLogins as $login) {
                $params['connected_logins'][$login] = $this->urlGenerator->linkToRoute($this->appName.'.settings.disconnectSocialLogin', [
                    'login' => $login,
                    'requesttoken' => Util::callRegister(),
                ]);
            }
        }
        return new TemplateResponse($this->appName, 'personal', $params);
    }

    private function getCustomProviders()
    {
        $result = [];
        $providers = $this->appConfig->getValueArray($this->appName, 'custom_providers');
        foreach ($providers as $providersType => $providerList) {
            foreach ($providerList as $provider) {
                $class = $this->providerService->getLoginClass($provider['name'], $provider, $providersType);
                $login = $this->container->get($class);
                $login->load();
                $title = $provider['title'];
                $result[$title] = ['url' => $login->getLink(), 'style' => $login->getClass()];
            }
        }

        return $result;
    }

    public function getSection()
    {
        return 'sociallogin';
    }

    public function getPriority()
    {
        return 0;
    }
}
