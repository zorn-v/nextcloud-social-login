<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IDBConnection;
use OCP\IAppConfig;

class SeparateProvidersNameAndTitle implements IRepairStep
{
    private $appName = 'sociallogin';

    public function __construct(
        private IAppConfig $appConfig,
        private IDBConnection $db
    ) {}

    public function getName()
    {
        return 'Separate user configured providers internal name and title. Also removes old unnecessary user config.';
    }

    public function run(IOutput $output)
    {
        if (version_compare($this->appConfig->getValueString($this->appName, 'installed_version'), '1.15.1') >= 0) {
            return;
        }

        $this->setProvidersName('openid_providers');
        $this->setProvidersName('custom_oidc_providers');

        //Removes old user config "password"
        $sql = "DELETE FROM `*PREFIX*preferences` WHERE `appid` = 'sociallogin' AND `configkey` = 'password'";
        $this->db->executeStatement($sql);
    }

    private function setProvidersName($configKey)
    {
        $providers = $this->appConfig->getValueArray($this->appName, $configKey);
        if (is_array($providers)) {
            foreach ($providers as &$provider) {
                if (!isset($provider['name'])) {
                    $provider['name'] = $provider['title'];
                }
            }
            $this->appConfig->setValueArray($this->appName, $configKey, $providers);
        }
    }
}
