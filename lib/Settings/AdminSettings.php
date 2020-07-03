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
        Util::addScript($this->appName, 'settings');
        $paramsNames = [
            'disable_registration',
            'create_disabled_users',
            'allow_login_connect',
            'prevent_create_email_exists',
            'update_profile_on_login',
            'no_prune_user_groups',
            'auto_create_groups',
            'restrict_users_wo_mapped_groups',
            'disable_notify_admins',
        ];
        $oauthProviders = [
            'google',
            'amazon',
            'facebook',
            'twitter',
            'GitHub',
            'discord',
            'slack',
            'QQ',
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
        $customProviders = json_decode($this->config->getAppValue($this->appName, 'custom_providers', '[]'), true);

        $params = [
            'app_name' => $this->appName,
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.saveAdmin'),
            'groups' => $groupNames,
            'tg_bot' => $this->config->getAppValue($this->appName, 'tg_bot'),
            'tg_token' => $this->config->getAppValue($this->appName, 'tg_token'),
            'tg_group' => $this->config->getAppValue($this->appName, 'tg_group'),
            'custom_providers' => $customProviders,
            'providers' => $providers,
        ];
        foreach ($paramsNames as $paramName) {
            $params['options'][$paramName] = $this->config->getAppValue($this->appName, $paramName);
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
