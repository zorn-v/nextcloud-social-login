<?php

namespace OCA\SocialLogin\Provider;

use Hybridauth\User;

class OAuth2 extends \Hybridauth\Adapter\OAuth2
{
    /**
    * {@inheritdoc}
    */
    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if ($this->isConnected()) {
            return true;
        }

        $code = filter_input(INPUT_GET, 'code');

        if (empty($code)) {
            $this->authenticateBegin();
        }
        return $this->authenticateFinish();
    }

    public function getUserProfile(){
      $userProfile = new User\Profile();
      return $userProfile;
    }
}
