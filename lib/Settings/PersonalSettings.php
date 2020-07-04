<?php

namespace OCA\SocialLogin\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\Util;
use OCA\SocialLogin\Db\SocialConnectDAO;

class PersonalSettings implements ISettings
{
    /** @var string */
    private $appName;
    /** @var IConfig */
    private $config;
    /** @var IURLGenerator */
    private $urlGenerator;
    /** @var IUserSession */
    private $userSession;
    /** @var SocialConnectDAO */
    private $socialConnect;

    public function __construct(
        $appName,
        IConfig $config,
        IURLGenerator $urlGenerator,
        IUserSession $userSession,
        SocialConnectDAO $socialConnect
    ) {
        $this->appName = $appName;
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->userSession = $userSession;
        $this->socialConnect = $socialConnect;
    }

    public function getForm()
    {
        Util::addScript($this->appName, 'personal');
        $uid = $this->userSession->getUser()->getUID();
        $params = [
            'providers' => [],
            'connected_logins' => [],
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.savePersonal'),
            'allow_login_connect' => $this->config->getAppValue($this->appName, 'allow_login_connect', false),
            'disable_password_confirmation' => $this->config->getUserValue($uid, $this->appName, 'disable_password_confirmation', false),
        ];
        if ($params['allow_login_connect']) {
            $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
            if (is_array($providers)) {
                foreach ($providers as $name => $provider) {
                    if ($provider['appid']) {
                        $providerUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.oauth', ['provider' => $name]);
                        if ($name === 'telegram') {
                            $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
                            $csp->addAllowedScriptDomain('telegram.org')
                                ->addAllowedFrameDomain('oauth.telegram.org')
                            ;
                            $manager = \OC::$server->getContentSecurityPolicyManager();
                            $manager->addDefaultPolicy($csp);

                            Util::addHeader('meta', [
                                'id' => 'tg-data',
                                'data-login' => $provider['appid'],
                                'data-redirect-url' => $providerUrl,
                            ]);
                            Util::addScript($this->appName, 'telegram');
                            continue;
                        }
                        $params['providers'][ucfirst($name)] = [
                            'url' => $providerUrl,
                        ];
                    }
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
        $providers = json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true) ?: [];
        foreach ($providers as $providersType => $providerList) {
            foreach ($providerList as $provider) {
                $name = $provider['name'];
                $title = $provider['title'];
                $result[$title] = [
                    'url' => $this->urlGenerator->linkToRoute($this->appName.'.login.custom', ['type' => $providersType, 'provider' => $name]),
                    'style' => isset($provider['style']) ? $provider['style'] : '',
                ];
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
