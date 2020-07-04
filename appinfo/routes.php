<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\SocialLogin\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
$customProviders = array_keys(OCA\SocialLogin\Service\ProviderService::TYPE_CLASSES);
$routes = [
    ['name' => 'settings#saveAdmin', 'url' => '/settings/save-admin', 'verb' => 'POST'],
    ['name' => 'settings#disconnectSocialLogin', 'url' => '/disconnect-social/{login}', 'verb' => 'GET'],
    ['name' => 'settings#savePersonal', 'url' => '/settings/save-personal', 'verb' => 'POST'],
    ['name' => 'login#oauth', 'url' => '/oauth/{provider}', 'verb' => 'GET'],
    ['name' => 'login#custom', 'url' => '/{type}/{provider}', 'verb' => 'GET'],
    ['name' => 'login#custom', 'url' => '/{type}/{provider}', 'postfix' => '.post', 'verb' => 'GET'],
];
/*foreach ($customProviders as $providerType) {
    $routes[] = ['name' => 'login#'.$providerType, 'url' => '/'.$providerType.'/{provider}', 'verb' => 'GET'];
    $routes[] = ['name' => 'login#'.$providerType, 'url' => '/'.$providerType.'/{provider}', 'postfix' => '.post', 'verb' => 'POST'];
}*/
return [
    'routes' => [
        ['name' => 'settings#saveAdmin', 'url' => '/settings/save-admin', 'verb' => 'POST'],
        ['name' => 'settings#disconnectSocialLogin', 'url' => '/disconnect-social/{login}', 'verb' => 'GET'],
        ['name' => 'settings#savePersonal', 'url' => '/settings/save-personal', 'verb' => 'POST'],
        ['name' => 'login#oauth', 'url' => '/oauth/{provider}', 'verb' => 'GET'],
        ['name' => 'login#custom', 'url' => '/{type}/{provider}', 'verb' => 'GET'],
        ['name' => 'login#custom', 'url' => '/{type}/{provider}', 'postfix' => '.post', 'verb' => 'GET'],
    ]
];
