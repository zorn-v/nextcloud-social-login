<?php

declare(strict_types=1);

namespace OCA\Provisioning_API\Controller;

use OCA\SocialLogin\Db\SocialConnectDAO;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;

class LinkController extends OCSController {

    /** @var SocialConnectDAO */
    private $socialConnect;

    public function __construct(
        $appName,
        IRequest $request,
        SocialConnectDAO $socialConnect
    ) {
        parent::__construct($appName, $request);
        $this->socialConnect = $socialConnect;
    }

    /**
     * @PasswordConfirmationRequired
     * @param string $uid
     * @param string $login
     * @return DataResponse
     */
    public function connectSocialLogin($uid, $login): DataResponse {
        $this->socialConnect->connectLogin($uid, $login);
        return new DataResponse();
    }

    /**
     * @PasswordConfirmationRequired
     * @param string $uid
     * @return DataResponse
     */
    public function connectSocialLogin($uid): DataResponse {
        $this->socialConnect->disconnectLogin($uid);
        return new DataResponse();
    }
}