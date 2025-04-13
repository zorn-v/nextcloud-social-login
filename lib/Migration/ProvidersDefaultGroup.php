<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IAppConfig;

class ProvidersDefaultGroup implements IRepairStep
{
    private $appName = 'sociallogin';

    public function __construct(
        private IAppConfig $appConfig
    ) {}

    public function getName()
    {
        return 'Set default group in each provider';
    }

    public function run(IOutput $output)
    {
        if (version_compare($this->appConfig->getValueString($this->appName, 'installed_version'), '1.15.1') >= 0) {
            return;
        }

        $defaultGroup = $this->appConfig->getValueString($this->appName, 'new_user_group');

        $this->setProvidersGroup('oauth_providers', $defaultGroup);
        $this->setProvidersGroup('openid_providers', $defaultGroup);
        $this->setProvidersGroup('custom_oidc_providers', $defaultGroup);
        $this->setProvidersGroup('custom_oauth2_providers', $defaultGroup);

        if ($defaultGroup) {
            $this->appConfig->setValueString($this->appName, 'tg_group', $defaultGroup);
        }

        $this->appConfig->deleteKey($this->appName, 'new_user_group');
    }

    private function setProvidersGroup($configKey, $defaultGroup)
    {
        $providers = $this->appConfig->getValueArray($this->appName, $configKey);
        if (is_array($providers)) {
            foreach ($providers as &$provider) {
                if (!isset($provider['defaultGroup'])) {
                    $provider['defaultGroup'] = $defaultGroup;
                }
            }
            $this->appConfig->setValueArray($this->appName, $configKey, $providers);
        }
    }
}
