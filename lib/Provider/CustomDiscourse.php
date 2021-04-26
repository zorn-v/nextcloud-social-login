<?php

namespace OCA\SocialLogin\Provider;

use Hybridauth\Adapter\Discourse;
use Hybridauth\User;

class CustomDiscourse extends Discourse
{
    public function getUserProfile()
    {
        $userProfile = parent::getUserProfile();

        if (null !== $groups = $userProfile->data['groups']) {
            $userProfile->data['groups'] = $this->strToArray($groups);
        }
        if ($groupMapping = $this->config->get('group_mapping')) {
            $userProfile->data['group_mapping'] = $groupMapping;
        }

        return $userProfile;
    }

    private function strToArray($str)
    {
        return array_filter(
            array_map('trim', explode(',', $str)),
            function ($val) { return $val !== ''; }
        );
    }
}
