<?php

namespace OCA\SocialLogin\Provider;

use Hybridauth\User;
use Hybridauth\Data;
use Hybridauth\Exception\Exception;

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

    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);
        if ($collection->exists('id_token')) {
            $idToken = $collection->get('id_token');
            //get payload from id_token
            $parts = explode('.', $idToken);
            list($headb64, $payload) = $parts;
            $data = base64_decode($payload);
            $this->storeData('user_data', $data);
        }
        else{
          throw new Exception('No id_token was found.');
        }
        return $collection;
    }


    public function getUserProfile(){
      $userData = $this->getStoredData('user_data');
      $user = json_decode($userData);
      $data = new Data\Collection($user);

      $userProfile = new User\Profile();
      $userProfile->identifier  = $data->get('sub');
      $userProfile->email       = $data->get('email');
      $userProfile->displayName = $data->get('name');

      return $userProfile;
    }
}
