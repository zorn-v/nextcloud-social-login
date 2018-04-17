<?php

use OCA\SocialLogin\Controller\SettingsController;

$controller = \OC::$server->query(SettingsController::class);

return $controller->renderPersonal();
