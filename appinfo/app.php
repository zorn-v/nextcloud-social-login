<?php

require __DIR__ . '/../3rdparty/autoload.php';

$app = new \OCA\SocialLogin\AppInfo\Application();
$app->register();

$userSession = \OC::$server->getUserSession();
$config = \OC::$server->getConfig();

if(!$userSession->isLoggedIn() &&
	\OC::$server->getRequest()->getPathInfo() === '/login') {
		$autoRedirect = $config->getAppValue('sociallogin', 'auto_redirect');
		$alternativeLogins = \OC_App::getAlternativeLogins();

		if($autoRedirect && count($alternativeLogins)==1){
			$url = $alternativeLogins[0]['href'];
			header('Location: ' . $url);
			exit();
		}
}
