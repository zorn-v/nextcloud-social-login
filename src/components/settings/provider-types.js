import { appName } from '../../common'

export default {
  custom_oidc: {
    title: t(appName, 'Custom OpenID Connect'),
    hasGroupMapping: true,
    fields: {
      name: {
        title: t(appName, 'Internal name'),
        type: 'text',
        required: true,
      },
      title: {
        title: t(appName, 'Title'),
        type: 'text',
        required: true,
      },
      authorizeUrl: {
        title: t(appName, 'Authorize url'),
        type: 'url',
        required: true,
      },
      tokenUrl: {
        title: t(appName, 'Token url'),
        type: 'url',
        required: true,
      },
      displayNameClaim: {
        title: t(appName, 'Display name claim (optional)'),
        type: 'text',
      },
      userInfoUrl: {
        title: t(appName, 'User info URL (optional)'),
        type: 'url',
        required: false,
      },
      logoutUrl: {
        title: t(appName, 'Logout URL (optional)'),
        type: 'url',
        required: false,
      },
      clientId: {
        title: t(appName, 'Client Id'),
        type: 'text',
        required: true,
      },
      clientSecret: {
        title: t(appName, 'Client Secret'),
        type: 'password',
        required: true,
      },
      scope: {
        title: t(appName, 'Scope'),
        type: 'text',
        required: true,
      },
      groupsClaim: {
        title: t(appName, 'Groups claim (optional)'),
        type: 'text',
        required: false,
      },
    }
  },
  openid: {
    title: 'OpenID',
    fields: {
      name: {
        title: t(appName, 'Internal name'),
        type: 'text',
        required: true,
      },
      title: {
        title: t(appName, 'Title'),
        type: 'text',
        required: true,
      },
      url: {
        title: t(appName, 'Identifier url'),
        type: 'url',
        required: true,
      },
    }
  },
  custom_oauth2: {
    title: t(appName, 'Custom OAuth2'),
    hasGroupMapping: true,
    fields: {
      name: {
        title: t(appName, 'Internal name'),
        type: 'text',
        required: true,
      },
      title: {
        title: t(appName, 'Title'),
        type: 'text',
        required: true,
      },
      apiBaseUrl: {
        title: t(appName, 'API Base URL'),
        type: 'url',
        required: true,
      },
      authorizeUrl: {
        title: t(appName, 'Authorize url (can be relative to base URL)'),
        type: 'text',
        required: true,
      },
      tokenUrl: {
        title: t(appName, 'Token url (can be relative to base URL)'),
        type: 'text',
        required: true,
      },
      profileUrl: {
        title: t(appName, 'Profile url (can be relative to base URL)'),
        type: 'text',
        required: true,
      },
      logoutUrl: {
        title: t(appName, 'Logout URL (optional)'),
        type: 'url',
        required: false,
      },
      clientId: {
        title: t(appName, 'Client Id'),
        type: 'text',
        required: true,
      },
      clientSecret: {
        title: t(appName, 'Client Secret'),
        type: 'password',
        required: true,
      },
      scope: {
        title: t(appName, 'Scope (optional)'),
        type: 'text',
        required: false,
      },
      profileFields: {
        title: t(appName, 'Profile Fields (optional, comma-separated)'),
        type: 'text',
        required: false,
      },
      displayNameClaim: {
        title: t(appName, 'Display name claim (optional)'),
        type: 'text',
      },
      groupsClaim: {
        title: t(appName, 'Groups claim (optional)'),
        type: 'text',
        required: false,
      },
    }
  },
  custom_oauth1: {
    title: t(appName, 'Custom OAuth1'),
    fields: {
      name: {
        title: t(appName, 'Internal name'),
        type: 'text',
        required: true,
      },
      title: {
        title: t(appName, 'Title'),
        type: 'text',
        required: true,
      },
      authorizeUrl: {
        title: t(appName, 'Authorize url'),
        type: 'text',
        required: true,
      },
      tokenUrl: {
        title: t(appName, 'Token url'),
        type: 'text',
        required: true,
      },
      profileUrl: {
        title: t(appName, 'Profile url'),
        type: 'text',
        required: true,
      },
      logoutUrl: {
        title: t(appName, 'Logout URL (optional)'),
        type: 'url',
        required: false,
      },
      clientId: {
        title: t(appName, 'Consumer key'),
        type: 'text',
        required: true,
      },
      clientSecret: {
        title: t(appName, 'Consumer Secret'),
        type: 'password',
        required: true,
      },
    }
  },
  custom_discourse: {
    title: t(appName, 'Custom Discourse'),
    hasGroupMapping: true,
    fields: {
      name: {
        title: t(appName, 'Internal name'),
        type: 'text',
        required: true,
      },
      title: {
        title: t(appName, 'Title'),
        type: 'text',
        required: true,
      },
      baseUrl: {
        title: t(appName, 'Base url'),
        type: 'text',
        required: true,
      },
      logoutUrl: {
        title: t(appName, 'Logout URL (optional)'),
        type: 'url',
        required: false,
      },
      ssoSecret: {
        title: t(appName, 'SSO Secret'),
        type: 'password',
        required: true,
      },
    }
  },
}
