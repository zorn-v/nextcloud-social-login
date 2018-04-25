<?php

namespace OCA\SocialLogin\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\Util;

class AdminSettings implements ISettings
{
    /** @var string */
    private $appName;
    /** @var IConfig */
    private $config;
    /** @var IURLGenerator */
    private $urlGenerator;
    /** @var IGroupManager */
    private $groupManager;

    public function __construct($appName, IConfig $config, IURLGenerator $urlGenerator, IGroupManager $groupManager)
    {
        $this->appName = $appName;
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->groupManager = $groupManager;
    }

    public function getForm()
    {
        Util::addStyle($this->appName, 'settings');
        Util::addScript($this->appName, 'settings');
        $paramsNames = [
            'new_user_group',
            'disable_registration',
            'allow_login_connect',
        ];
        $oauthProviders = [
            'facebook',
            'google',
            'twitter',
            'GitHub',
        ];
        $groupNames = [];
        $groups = $this->groupManager->search('');
        foreach ($groups as $group) {
            $groupNames[] = $group->getGid();
        }
        $providers = [];
        $savedProviders = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        foreach ($oauthProviders as $provider) {
            if (array_key_exists($provider, $savedProviders)) {
                $providers[$provider] = $savedProviders[$provider];
            } else {
                $providers[$provider] = [
                    'appid' => '',
                    'secret' => '',
                ];
            }
        }
        $openIdProviders = json_decode($this->config->getAppValue($this->appName, 'openid_providers', '[]'), true);
        if (!is_array($openIdProviders)) {
            $openIdProviders = [];
        }
        $custom_oidcProviders = json_decode($this->config->getAppValue($this->appName, 'custom_oidc_providers', '[]'), true);
        if (!is_array($custom_oidcProviders)) {
            $custom_oidcProviders = [];
        }

        $params = [
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.saveAdmin'),
            'groups' => $groupNames,
            'providers' => $providers,
            'openid_providers' => $openIdProviders,
            'custom_oidc_providers' => $custom_oidcProviders,
        ];
        foreach ($paramsNames as $paramName) {
            $params[$paramName] = $this->config->getAppValue($this->appName, $paramName);
        }
        return new TemplateResponse($this->appName, 'admin', $params);
    }

    public function getSection()
    {
        return $this->appName;
    }

    public function getPriority()
    {
        return 0;
    }
}
