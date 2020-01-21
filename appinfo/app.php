<?php

require __DIR__ . '/../3rdparty/autoload.php';

$app = \OC::$server->query(OCA\SocialLogin\AppInfo\Application::class);
$app->register();
