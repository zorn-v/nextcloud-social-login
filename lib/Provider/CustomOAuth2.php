<?php

namespace OCA\SocialLogin\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Exception\AuthorizationDeniedException;
use Hybridauth\User;

class CustomOAuth2 extends OAuth2
{
    /**
     * @return User\Profile
     * @throws UnexpectedApiResponseException
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    public function getUserProfile()
    {
        $profileUrl = $this->config->get('endpoints')['profile_url'];
	$headers = ['X-Scope' => $this->config->get('scope')];
  
        $response = $this->apiRequest($profileUrl, 'GET', [], $headers);       
 
        $data = new Data\Collection($response);

        $userProfile = new User\Profile();
	
	$userProfile->identifier = $response->id;
	$userProfile->email = $response->email;
        $userProfile->displayName = $response->first_name . ' ' . $response->last_name . ' / ' . $response->nickname;
	$userProfile->firstName = $response->first_name;
	$userProfile->lastName = $response->last_name;
        $userProfile->language = $response->correspondence_language;

        if($response->kantonalverband_id !== 3) {
           $userProfile->data['groups'] = ['extern'];
        } elseif (null !== $groups = $this->getGroups($data)) {
           $userProfile->data['groups'] = $groups;
        }
        
	if ($groupMapping = $this->config->get('group_mapping')) {        
    	    $userProfile->data['group_mapping'] = $this->mapGroup($groupMapping);
        }
	
        return $userProfile;
    }

    protected function mapGroup($groups) {
	foreach($groups as $num => $group) {
	   if(sizeof($this->strToArray($num)) > 1) {
	      unset($groups[$num]);
	      foreach($this->strToArray($num) as $key => $val) {
                $groups[$val] = $group;
 	      }
	   }
        }
	return $groups;
    }

    protected function getGroups(Data\Collection $data)
    {
	if ($groupsClaim = $this->config->get('groups_claim')) {
            $nestedClaims = explode('.', $groupsClaim);
            $claim = array_shift($nestedClaims);
            $groups = $data->get($claim);
            while (count($nestedClaims) > 0) {
                $claim = array_shift($nestedClaims);
                if (!isset($groups->{$claim})) {
                    $groups = [];
                    break;
                }
                $groups = $groups->{$claim};
            }
	   
            foreach ($groups as $num => $role) {
		if(preg_match('[Biber|Wolf|Leitwolf|Pfadi|Leitpfadi|Pio]', $groups[$num]->role_name)) {
		   throw new AuthorizationDeniedException('Zugriff nicht erlaubt!');
		} else {
		   $groups[$num] = $groups[$num]->group_id;
		}
	    }
	    return $groups;
	}
        return null;
    }

    private function strToArray($str)
    {
        return array_filter(
            array_map('trim', explode(',', $str)),
            function ($val) { return $val !== ''; }
        );
    }
}
