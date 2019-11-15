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
        $disable_registration,
        $create_disabled_users,
        $allow_login_connect,
        $prevent_create_email_exists,
        $update_profile_on_login,
        $no_prune_user_groups,
        $auto_create_groups,
        $restrict_users_wo_mapped_groups,
        $disable_notify_admins,
        $providers,
        $tg_bot,
        $tg_token,
        $tg_group,
        $openid_providers,
        $custom_oidc_providers,
        $custom_oauth2_providers
    ) {

        $this->config->setAppValue($this->appName, 'disable_registration', $disable_registration ? true : false);
        $this->config->setAppValue($this->appName, 'create_disabled_users', $create_disabled_users ? true : false);
        $this->config->setAppValue($this->appName, 'allow_login_connect', $allow_login_connect ? true : false);
        $this->config->setAppValue($this->appName, 'prevent_create_email_exists', $prevent_create_email_exists ? true : false);
        $this->config->setAppValue($this->appName, 'update_profile_on_login', $update_profile_on_login ? true : false);
        $this->config->setAppValue($this->appName, 'no_prune_user_groups', $no_prune_user_groups ? true : false);
        $this->config->setAppValue($this->appName, 'auto_create_groups', $auto_create_groups ? true : false);
        $this->config->setAppValue($this->appName, 'restrict_users_wo_mapped_groups', $restrict_users_wo_mapped_groups ? true : false);
        $this->config->setAppValue($this->appName, 'disable_notify_admins', $disable_notify_admins ? true : false);
        $this->config->setAppValue($this->appName, 'oauth_providers', json_encode($providers));
        $this->config->setAppValue($this->appName, 'tg_bot', $tg_bot);
        $this->config->setAppValue($this->appName, 'tg_token', $tg_token);
        $this->config->setAppValue($this->appName, 'tg_group', $tg_group);

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
        return new RedirectResponse($this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section'=>'sociallogin']));
    }
}
