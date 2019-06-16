<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IDBConnection;
use OCP\IConfig;

class ProvidersDefaultGroup implements IRepairStep
{
    /** @var IConfig */
    private $config;

    /** @var IDBConnection */
    private $db;

    private $appName = 'sociallogin';

    public function __construct(IConfig $config, IDBConnection $db)
    {
        $this->config = $config;
        $this->db = $db;
    }

    public function getName()
    {
        return 'Set default group in each provider';
    }

    public function run(IOutput $output)
    {
        $defaultGroup = $this->config->getAppValue($this->appName, 'new_user_group');

        $this->setProvidersGroup('oauth_providers', $defaultGroup);
        $this->setProvidersGroup('openid_providers', $defaultGroup);
        $this->setProvidersGroup('custom_oidc_providers', $defaultGroup);
        $this->setProvidersGroup('custom_oauth2_providers', $defaultGroup);

        if ($defaultGroup) {
            $this->config->setAppValue($this->appName, 'tg_group', $defaultGroup);
        }

        $this->config->deleteAppValue($this->appName, 'new_user_group');
    }

    private function setProvidersGroup($configKey, $defaultGroup)
    {
        $providers = json_decode($this->config->getAppValue($this->appName, $configKey), true);
        if (is_array($providers)) {
            foreach ($providers as &$provider) {
                if (!isset($provider['defaultGroup'])) {
                    $provider['defaultGroup'] = $defaultGroup;
                }
            }
            $this->config->setAppValue($this->appName, $configKey, json_encode($providers));
        }
    }
}
