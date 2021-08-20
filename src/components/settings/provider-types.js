export default {
  custom_oidc: {
    title: 'Custom OpenID Connect',
    hasGroupMapping: true,
    fields: {
      name: {
        title: 'Internal name',
        type: 'text',
        required: true,
      },
      title: {
        title: 'Title',
        type: 'text',
        required: true,
      },
      authorizeUrl: {
        title: 'Authorize url',
        type: 'url',
        required: true,
      },
      tokenUrl: {
        title: 'Token url',
        type: 'url',
        required: true,
      },
      displayNameClaim: {
        title: 'Display name claim (optional)',
        type: 'text',
      },
      userInfoUrl: {
        title: 'User info URL (optional)',
        type: 'url',
        required: false,
      },
      logoutUrl: {
        title: 'Logout URL (optional)',
        type: 'url',
        required: false,
      },
      clientId: {
        title: 'Client Id',
        type: 'text',
        required: true,
      },
      clientSecret: {
        title: 'Client Secret',
        type: 'password',
        required: true,
      },
      scope: {
        title: 'Scope',
        type: 'text',
        required: true,
      },
      groupsClaim: {
        title: 'Groups claim (optional)',
        type: 'text',
        required: false,
      },
      saveTokens: {
        title: 'Save and refresh tokens in local database',
        type: 'checkbox',
        required: false,
      }
    }
  },
  openid: {
    title: 'OpenID',
    fields: {
      name: {
        title: 'Internal name',
        type: 'text',
        required: true,
      },
      title: {
        title: 'Title',
        type: 'text',
        required: true,
      },
      url: {
        title: 'Identifier url',
        type: 'url',
        required: true,
      },
    }
  },
  custom_oauth2: {
    title: 'Custom OAuth2',
    hasGroupMapping: true,
    fields: {
      name: {
        title: 'Internal name',
        type: 'text',
        required: true,
      },
      title: {
        title: 'Title',
        type: 'text',
        required: true,
      },
      apiBaseUrl: {
        title: 'API Base URL',
        type: 'url',
        required: true,
      },
      authorizeUrl: {
        title: 'Authorize url (can be relative to base URL)',
        type: 'text',
        required: true,
      },
      tokenUrl: {
        title: 'Token url (can be relative to base URL)',
        type: 'text',
        required: true,
      },
      profileUrl: {
        title: 'Profile url (can be relative to base URL)',
        type: 'text',
        required: true,
      },
      logoutUrl: {
        title: 'Logout URL (optional)',
        type: 'url',
        required: false,
      },
      clientId: {
        title: 'Client Id',
        type: 'text',
        required: true,
      },
      clientSecret: {
        title: 'Client Secret',
        type: 'password',
        required: true,
      },
      scope: {
        title: 'Scope (optional)',
        type: 'text',
        required: false,
      },
      profileFields: {
        title: 'Profile Fields (optional, comma-separated)',
        type: 'text',
        required: false,
      },
      groupsClaim: {
        title: 'Groups claim (optional)',
        type: 'text',
        required: false,
      },
    }
  },
  custom_oauth1: {
    title: 'Custom OAuth1',
    fields: {
      name: {
        title: 'Internal name',
        type: 'text',
        required: true,
      },
      title: {
        title: 'Title',
        type: 'text',
        required: true,
      },
      authorizeUrl: {
        title: 'Authorize url',
        type: 'text',
        required: true,
      },
      tokenUrl: {
        title: 'Token url',
        type: 'text',
        required: true,
      },
      profileUrl: {
        title: 'Profile url',
        type: 'text',
        required: true,
      },
      logoutUrl: {
        title: 'Logout URL (optional)',
        type: 'url',
        required: false,
      },
      clientId: {
        title: 'Consumer key',
        type: 'text',
        required: true,
      },
      clientSecret: {
        title: 'Consumer Secret',
        type: 'password',
        required: true,
      },
    }
  },
}
