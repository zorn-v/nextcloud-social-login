<?php

namespace OCA\SocialLogin\AlternativeLogin;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\IAppConfig;
use OCP\Util;

class TelegramLogin extends SocialLogin
{
    public function __construct(
        private $appName,
        private IEventDispatcher $dispatcher,
        private IAppConfig $appConfig
    ) {}

    public function getLink(): string
    {
        return 'javascript:;';
    }

    public function getClass(): string
    {
        return 'telegram';
    }

    public function load(): void
    {
        parent::load();
        $this->dispatcher->addListener(AddContentSecurityPolicyEvent::class, function ($event) {
            $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
            $csp->addAllowedScriptDomain('telegram.org');
            $event->addPolicy($csp);
        });
        $providers = $this->appConfig->getValueArray($this->appName, 'oauth_providers');
        $token = $providers['telegram']['secret'] ?? '';
        list($botId) = explode(':', $token);
        Util::addHeader('meta', [
            'id' => 'tg-data',
            'data-bot-id' => $botId,
            'data-redirect-url' => parent::getLink(),
        ]);
        Util::addScript($this->appName, 'telegram');
    }
}
