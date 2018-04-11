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
            'new_user_group'
        ];
        $groupNames = [];
        $groups = $this->groupManager->search('');
        foreach ($groups as $group) {
            $groupNames[] = $group->getGid();
        }
        $params = [
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.saveAdmin'),
            'groups' => $groupNames,
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
