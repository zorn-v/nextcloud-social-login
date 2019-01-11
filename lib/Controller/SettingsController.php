<?php

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Util;
use OCA\SocialLogin\Db\SocialConnectDAO;

class SettingsController extends Controller
{
    /** @var IConfig */
    private $config;
    /** @var IURLGenerator */
    private $urlGenerator;
    /** @var IUserSession */
    private $userSession;
    /** @var IL10N */
    private $l;
    /** @var SocialConnectDAO */
    private $socialConnect;

    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        IURLGenerator $urlGenerator,
        IUserSession $userSession,
        IL10N $l,
        SocialConnectDAO $socialConnect
    ) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->userSession = $userSession;
        $this->l = $l;
        $this->socialConnect = $socialConnect;
    }

    public function saveAdmin(
        $new_user_group,
        $disable_registration,
        $allow_login_connect,
        $prevent_create_email_exists,
        $providers,
        $tg_bot,
        $tg_token,
        $openid_providers,
        $custom_oidc_providers,
        $custom_oauth2_providers
    ) {
        $this->config->setAppValue($this->appName, 'new_user_group', $new_user_group);
        $this->config->setAppValue($this->appName, 'disable_registration', $disable_registration ? true : false);
        $this->config->setAppValue($this->appName, 'allow_login_connect', $allow_login_connect ? true : false);
        $this->config->setAppValue($this->appName, 'prevent_create_email_exists', $prevent_create_email_exists ? true : false);
        $this->config->setAppValue($this->appName, 'oauth_providers', json_encode($providers));
        $this->config->setAppValue($this->appName, 'tg_bot', $tg_bot);
        $this->config->setAppValue($this->appName, 'tg_token', $tg_token);

        $openid_providers = $openid_providers ?: [];
        $custom_oidc_providers = $custom_oidc_providers ?: [];
        $custom_oauth2_providers = $custom_oauth2_providers ?: [];
        try {
            $names = array_keys($providers);
            $this->checkProviders($openid_providers, $names);
            $this->checkProviders($custom_oidc_providers, $names);
            $this->checkProviders($custom_oauth2_providers, $names);
        } catch (\Exception $e) {
            return new JSONResponse(['message' => $e->getMessage()]);
        }

        if (is_array($openid_providers)) {
            $this->config->setAppValue($this->appName, 'openid_providers', json_encode(array_values($openid_providers)));
        }
        if (is_array($custom_oidc_providers)) {
            $this->config->setAppValue($this->appName, 'custom_oidc_providers', json_encode(array_values($custom_oidc_providers)));
        }
        if (is_array($custom_oauth2_providers)) {
            $this->config->setAppValue($this->appName, 'custom_oauth2_providers', json_encode(array_values($custom_oauth2_providers)));
        }
        return new JSONResponse(['success' => true]);
    }

    private function checkProviders($providers, &$names)
    {
        if (!is_array($providers)) {
            return;
        }
        foreach ($providers as $provider) {
            $name = $provider['name'];
            if (empty($name)) {
                throw new \Exception($this->l->t('Provider name cannot be empty'));
            }
            if (in_array($name, $names)) {
                throw new \Exception($this->l->t('Duplicate provider name "%s"', $name));
            }
            if (preg_match('#[^0-9a-z_.@-]#i', $name)) {
                throw new \Exception($this->l->t('Invalid provider name "%s". Allowed characters "0-9a-z_.@-"', $name));
            }
            $names[] = $name;
        }
    }

    public function renderPersonal()
    {
        Util::addScript($this->appName, 'personal');
        $uid = $this->userSession->getUser()->getUID();
        $params = [
            'providers' => [],
            'connected_logins' => [],
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.savePersonal'),
            'allow_login_connect' => $this->config->getAppValue($this->appName, 'allow_login_connect', false),
            'disable_password_confirmation' => $this->config->getUserValue($uid, $this->appName, 'disable_password_confirmation', false),
        ];
        if ($params['allow_login_connect']) {
            if ($params['tg_bot'] = $this->config->getAppValue($this->appName, 'tg_bot')) {
                $params['tg_redirect_url'] = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.telegram');
                $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
                $csp->addAllowedScriptDomain('telegram.org')
                    ->addAllowedFrameDomain('oauth.telegram.org')
                ;
                $manager = \OC::$server->getContentSecurityPolicyManager();
                $manager->addDefaultPolicy($csp);
            }

            $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
            if (is_array($providers)) {
                foreach ($providers as $name => $provider) {
                    if ($provider['appid']) {
                        $params['providers'][ucfirst($name)] = $this->urlGenerator->linkToRoute($this->appName.'.login.oauth', ['provider' => $name]);
                    }
                }
            }
            $params['providers'] = array_merge($params['providers'], $this->getProviders('openid'));
            $params['providers'] = array_merge($params['providers'], $this->getProviders('custom_oidc'));
            $params['providers'] = array_merge($params['providers'], $this->getProviders('custom_oauth2'));

            $connectedLogins = $this->socialConnect->getConnectedLogins($uid);
            foreach ($connectedLogins as $login) {
                $params['connected_logins'][$login] = $this->urlGenerator->linkToRoute($this->appName.'.settings.disconnectSocialLogin', [
                    'login' => $login,
                    'requesttoken' => Util::callRegister(),
                ]);
            }
        }
        return (new TemplateResponse($this->appName, 'personal', $params, ''))->render();
    }

    private function getProviders($providersType)
    {
        $result = [];
        $providers = json_decode($this->config->getAppValue($this->appName, $providersType.'_providers', '[]'), true);
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                $name = $provider['name'];
                $title = $provider['title'];
                $result[$title] = $this->urlGenerator->linkToRoute($this->appName.'.login.'.$providersType, ['provider' => $name]);
            }
        }
        return $result;
    }

    /**
     * @NoAdminRequired
     * @PasswordConfirmationRequired
     */
    public function savePersonal($disable_password_confirmation)
    {
        $uid = $this->userSession->getUser()->getUID();
        $this->config->setUserValue($uid, $this->appName, 'disable_password_confirmation', $disable_password_confirmation ? 1 : 0);
        return new JSONResponse(['success' => true]);
    }

    /**
     * @NoAdminRequired
     */
    public function disconnectSocialLogin($login)
    {
        $this->socialConnect->disconnectLogin($login);
        return new RedirectResponse($this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section'=>'additional']));
    }
}
