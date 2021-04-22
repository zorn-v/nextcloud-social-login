<?php

namespace OCA\SocialLogin\WebDav;

use OCA\SocialLogin\Provider\CustomOpenIDConnect;

use OC\User\LoginException;
use Hybridauth\User;
use Hybridauth\Data;
use Hybridauth\Exception\Exception;

class CustomWebDavAdapter extends CustomOpenIDConnect
{
    public function authenticate() {
        $token = $this->getStorage()->get(WebDavProviderService::BEARER_TOKEN);
        if(is_null($token)) {
            throw new LoginException("Could not find bearer token");
        }
        $claims = $this->getStorage()->get(WebDavProviderService::CLAIMS);
        if(is_null($claims)) {
            throw new LoginException("could not find claims");
        }
        $this->storeData('access_token', $token);
        $this->storeData('token_type', 'Bearer');
        $this->storeData('user_data', base64_decode($claims));
        $this->initialize();
        return null;
    }
}
