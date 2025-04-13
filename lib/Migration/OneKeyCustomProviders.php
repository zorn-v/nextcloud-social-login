<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IAppConfig;

class OneKeyCustomProviders implements IRepairStep
{
    private $appName = 'sociallogin';

    public function __construct(
        private IAppConfig $appConfig
    ) {}

    public function getName()
    {
        return 'Migrate custom providers to one config key';
    }

    public function run(IOutput $output)
    {
        if (version_compare($this->appConfig->getValueString($this->appName, 'installed_version'), '3.1.0') > 0) {
            return;
        }
        $customProviders = $this->appConfig->getValueArray($this->appName, 'custom_providers');
        $customProvidersNames = ['openid', 'custom_oidc', 'custom_oauth2'];
        foreach ($customProvidersNames as $providerName) {
            $configKey = $providerName.'_providers';
            $providers = $this->appConfig->getValueArray($this->appName, $configKey);
            if (!empty($providers)) {
                $customProviders[$providerName] = $providers;
            }
            $this->appConfig->deleteKey($this->appName, $configKey);
        }
        $this->appConfig->setValueArray($this->appName, 'custom_providers', $customProviders);
    }
}
