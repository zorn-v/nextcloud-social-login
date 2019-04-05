<?php
/*!
 * Hybridauth
 * https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
 *  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
 */

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

/**
 * LinkedIn OAuth2 provider adapter.
 */
class LinkedIn extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    public $scope = 'r_liteprofile r_emailaddress w_member_social';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.linkedin.com/v2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.linkedin.com/oauth/v2/authorization';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://docs.microsoft.com/en-us/linkedin/shared/authentication/authorization-code-flow';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $fields = [
            'id',
            'firstName',
            'lastName',
            'profilePicture(displayImage~:playableStreams)',
        ];


        $response = $this->apiRequest('me?projection=(' . implode(',', $fields) . ')');
        $data     = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->firstName   = $data->filter('firstName')->filter('localized')->get('en_US');
        $userProfile->lastName    = $data->filter('lastName')->filter('localized')->get('en_US');
        $userProfile->photoURL    = $this->getUserPhotoUrl($data->filter('profilePicture')->filter('displayImage~')->get('elements'));
        $userProfile->email       = $this->getUserEmail();
        $userProfile->emailVerified = $userProfile->email;

        $userProfile->displayName = trim($userProfile->firstName . ' ' . $userProfile->lastName);

        return $userProfile;
    }

    /**
     * Returns a user photo.
     *
     * @param array $elements
     *   List of file identifiers related to this artifact.
     *
     * @return string
     *   The user photo URL.
     *
     * @see https://docs.microsoft.com/en-us/linkedin/shared/references/v2/profile/profile-picture
     */
    public function getUserPhotoUrl($elements)
    {
        if (is_array($elements)) {
            // Get the largest picture from the list which is the last one.
            $element = end($elements);
            if (!empty($element->identifiers)) {
                return reset($element->identifiers)->identifier;
            }
        }

        return NULL;
    }

    /**
     * Returns an email address of user.
     *
     * @return string
     *   The user email address.
     */
    public function getUserEmail()
    {
        $response = $this->apiRequest('emailAddress?q=members&projection=(elements*(handle~))');
        $data     = new Data\Collection($response);

        foreach ($data->filter('elements')->toArray() as $element) {
            $item = new Data\Collection($element);

            if ($email = $item->filter('handle~')->get('emailAddress')) {
                return $email;
            }
        }

        return NULL;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/share-on-linkedin
     */
    public function setUserStatus($status, $userID = null)
    {
        if (is_string($status)) {
            $status = [
                'author' => 'urn:li:person:' . $userID,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $status,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];
        }


        $headers = [
            'Content-Type' => 'application/json',
            'x-li-format'  => 'json',
            'X-Restli-Protocol-Version'  => '2.0.0',
        ];

        $response = $this->apiRequest("ugcPosts", 'POST', $status, $headers);

        return $response;
    }
}
