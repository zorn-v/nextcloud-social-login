<?php

namespace OCA\SocialLogin\Provider;

class OpenID extends \Hybridauth\Provider\OpenID
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

        $openid_mode = filter_input($_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET, 'openid_mode');

        if (empty($openid_mode)) {
            $this->authenticateBegin();
        } else {
            return $this->authenticateFinish();
        }

        return null;
    }
}
