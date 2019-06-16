<?php

namespace OCA\SocialLogin\Provider;

use Hybridauth\User;
use Hybridauth\Data;
use Hybridauth\Exception\Exception;
use Hybridauth\Adapter\OAuth2;

class CustomOpenIDConnect extends OAuth2
{
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
        } else {
            throw new Exception('No id_token was found.');
        }
        return $collection;
    }

    public function getUserProfile()
    {
        $userData = $this->getStoredData('user_data');
        $user = json_decode($userData);
        $data = new Data\Collection($user);

        $userProfile = new User\Profile();
        $userProfile->identifier  = $data->get('sub');
        $userProfile->displayName = $data->get('name');
        $userProfile->photoURL    = $data->get('picture');
        $userProfile->email       = $data->get('email');
        if ($groups = $this->getGroups($data)) {
            $userProfile->data['groups'] = $groups;
        }
        if ($groupMapping = $this->config->get('group_mapping')) {
            $userProfile->data['group_mapping'] = $groupMapping;
        }

        $userInfoUrl = trim($this->config->get('endpoints')['user_info_url']);
        if (!empty($userInfoUrl) && !isset(
            $userProfile->displayName,
            $userProfile->photoURL,
            $userProfile->email,
            $userProfile->data['groups']
        )) {
            $profile = new Data\Collection( $this->apiRequest($userInfoUrl) );
            if (empty($userProfile->displayName)) {
                $userProfile->displayName = $profile->get('name') ?: $profile->get('nickname');
            }
            if (empty($userProfile->photoURL)) {
                $userProfile->photoURL = $profile->get('picture') ?: $profile->get('avatar');
                if (preg_match('#<img.+src=["\'](.+?)["\']#', $userProfile->photoURL, $m)) {
                    $userProfile->photoURL = $m[1];
                }
            }
            if (empty($userProfile->email)) {
                $userProfile->email = $profile->get('email');
            }
            if (empty($userProfile->data['groups']) && $groups = $this->getGroups($profile)) {
                $userProfile->data['groups'] = $groups;
            }
        }

        return $userProfile;
    }

    private function getGroups(Data\Collection $data)
    {
        $groupsClaim = $this->config->get('groups_claim');
        if ($groups = $data->get($groupsClaim)) {
            if (is_array($groups)) {
                return $groups;
            } elseif (is_string($groups)) {
                return array_filter(
                    array_map('trim', explode(',', $groups)),
                    function ($val) { return $val !== ''; }
                );
            }
        }
        return null;
    }
}
