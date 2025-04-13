<?php

namespace OCA\SocialLogin\Settings;

use OCA\SocialLogin\Service\ProviderService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\Util;

class AdminSettings implements ISettings
{
    public function __construct(
        private $appName,
        private IAppConfig $appConfig,
        private IURLGenerator $urlGenerator,
        private IGroupManager $groupManager
    ) {}

    public function getForm()
    {
        Util::addScript($this->appName, 'settings');

        $groupNames = [];
        $groups = $this->groupManager->search('');
        foreach ($groups as $group) {
            $groupNames[] = $group->getGid();
        }
        $providers = [];
        $savedProviders = $this->appConfig->getValueArray($this->appName, 'oauth_providers');
        foreach (ProviderService::DEFAULT_PROVIDERS as $provider) {
            if (array_key_exists($provider, $savedProviders)) {
                $providers[$provider] = $savedProviders[$provider];
            } else {
                $providers[$provider] = [
                    'appid' => '',
                    'secret' => '',
                ];
            }
        }
        $customProviders = $this->appConfig->getValueArray($this->appName, 'custom_providers');

        $params = [
            'app_name' => $this->appName,
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.saveAdmin'),
            'groups' => $groupNames,
            'custom_providers' => $customProviders,
            'providers' => $providers,
        ];
        foreach (ProviderService::OPTIONS as $paramName) {
            $params['options'][$paramName] = $this->appConfig->getValueBool($this->appName, $paramName);
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
