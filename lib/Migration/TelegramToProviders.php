<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IAppConfig;

class TelegramToProviders implements IRepairStep
{
    private $appName = 'sociallogin';

    public function __construct(
        private IAppConfig $appConfig
    ) {}

    public function getName()
    {
        return 'Move telegram config to comman providers';
    }

    public function run(IOutput $output)
    {
        if (version_compare($this->appConfig->getValueString($this->appName, 'installed_version'), '3.1.1') > 0) {
            return;
        }
        $providers = $this->appConfig->getValueArray($this->appName, 'oauth_providers');
        $providers['telegram'] = [
            'appid' => $this->appConfig->getValueString($this->appName, 'tg_bot'),
            'secret' => $this->appConfig->getValueString($this->appName, 'tg_token'),
            'defaultGroup' => $this->appConfig->getValueString($this->appName, 'tg_group'),
        ];
        $this->appConfig->setValueArray($this->appName, 'oauth_providers', $providers);
        $this->appConfig->deleteKey($this->appName, 'tg_bot');
        $this->appConfig->deleteKey($this->appName, 'tg_token');
        $this->appConfig->deleteKey($this->appName, 'tg_group');
    }
}
