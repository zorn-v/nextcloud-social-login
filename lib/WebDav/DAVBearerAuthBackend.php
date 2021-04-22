<?php

namespace OCA\SocialLogin\WebDav;

use \OCA\SocialLogin\Db\PublicKeyMapper;

use OCP\ISession;
use OCP\IUserSession;
use OCP\ILogger;
use OCP\IConfig;

use \OCP\EventDispatcher\IEventListener;
use \OCP\EventDispatcher\Event;

use Sabre\DAV\Auth\Backend\AbstractBearer;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class DAVBearerAuthBackend extends AbstractBearer implements IEventListener {
	/** @var IUserSession */
	private $userSession;
	/** @var ISession */
	private $session;
	/** @var string */
	private $principalPrefix;
	/** @var ILogger */
	private $logger;
	/** @var PublicKeyMapper */
	private $publicKeyMapper;
	/** @var string */
	private string $appName;
	/** @var WebDavProviderService */
	private $providerService;

	/**
	 * @param IUserSession $userSession
	 * @param ISession $session
	 * @param string $principalPrefix
	 */
	public function __construct(IUserSession $userSession,
								ISession $session,
								string $appName,
								WebDavProviderService $providerService,
								$principalPrefix = 'principals/users/') {
		$this->userSession = $userSession;
		$this->session = $session;
		$this->appName = $appName;
		$this->providerService = $providerService;
		$this->principalPrefix = $principalPrefix;
		$this->context = ["app" => $this->appName];

		// setup realm
		$defaults = new \OCP\Defaults();
		$this->realm = $defaults->getName();

		$this->logger = \OC::$server->getLogger();
	}

	private function setupUserFs($userId) {
		\OC_Util::setupFS($userId);
		$this->session->close();
		return $this->principalPrefix . $userId;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateBearerToken($bearerToken) {
        \OC_Util::setupFS(); //login hooks may need early access to the filesystem

		$this->logger->debug("Validating bearer token", $this->context);

		if (!$this->userSession->isLoggedIn()) {
			try {
				$this->providerService->handleWebDav($bearerToken);
			} catch (\Exception $e) {
				$this->logger->debug("Bearer token validation failed with {$e}", $this->context);
			}
        }

		if ($this->userSession->isLoggedIn()) {
			return $this->setupUserFs($this->userSession->getUser()->getUID());
		}        

		return false;
	}

	/**
	 * \Sabre\DAV\Auth\Backend\AbstractBearer::challenge sets an WWW-Authenticate
	 * header which some DAV clients can't handle. Thus we override this function
	 * and make it simply return a 401.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function challenge(RequestInterface $request, ResponseInterface $response) {
		$response->setStatus(401);
	}

	/**
	 * Implements IEventListener::handle.
	 * Registers this class as an authentication backend with Sabre WebDav.
	 */
	public function handle(Event $event): void {
        $plugin = $event->getServer()->getPlugin('auth');

        if($plugin != null) {
            $plugin->addBackend($this);
            $this->logger->debug("SocialLogin WebDav plugin registered!", $this->context);
        }
    }
}

