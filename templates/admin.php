<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */

$providersData = [
    'openid' => [
        'title' => 'OpenID',
        'fields' => [
            'name' => [
                'title' => 'Internal name',
                'type' => 'text',
                'required' => true,
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'required' => true,
            ],
            'url' => [
                'title' => 'Identifier url',
                'type' => 'url',
                'required' => true,
            ],
        ]
    ],
    'custom_oidc' => [
        'title' => 'Custom OpenID Connect',
        'fields' => [
            'name' => [
                'title' => 'Internal name',
                'type' => 'text',
                'required' => true,
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'required' => true,
            ],
            'authorizeUrl' => [
                'title' => 'Authorize url',
                'type' => 'url',
                'required' => true,
            ],
            'tokenUrl' => [
                'title' => 'Token url',
                'type' => 'url',
                'required' => true,
            ],
            'userInfoUrl' => [
                'title' => 'User info URL (optional)',
                'type' => 'url',
                'required' => false,
            ],
            'clientId' => [
                'title' => 'Client Id',
                'type' => 'text',
                'required' => true,
            ],
            'clientSecret' => [
                'title' => 'Client Secret',
                'type' => 'password',
                'required' => true,
            ],
            'scope' => [
                'title' => 'Scope',
                'type' => 'text',
                'required' => true,
            ],
        ]
    ],
    'custom_oauth2' => [
        'title' => 'Custom OAuth2',
        'fields' => [
            'name' => [
                'title' => 'Internal name',
                'type' => 'text',
                'required' => true,
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'required' => true,
            ],
            'apiBaseUrl' => [
                'title' => 'API Base URL',
                'type' => 'url',
                'required' => true,
            ],
            'authorizeUrl' => [
                'title' => 'Authorize url (can be relative to base URL)',
                'type' => 'text',
                'required' => true,
            ],
            'tokenUrl' => [
                'title' => 'Token url (can be relative to base URL)',
                'type' => 'text',
                'required' => true,
            ],
            'profileUrl' => [
                'title' => 'Profile url (can be relative to base URL)',
                'type' => 'text',
                'required' => true,
            ],
            'clientId' => [
                'title' => 'Client Id',
                'type' => 'text',
                'required' => true,
            ],
            'clientSecret' => [
                'title' => 'Client Secret',
                'type' => 'password',
                'required' => true,
            ],
            'scope' => [
                'title' => 'Scope (optional)',
                'type' => 'text',
                'required' => false,
            ],
            'profileFields' => [
                'title' => 'Profile Fields (optional, comma-separated)',
                'type' => 'text',
                'required' => false,
            ],
        ]
    ],
];
?>
<div id="sociallogin" class="section">
    <form id="sociallogin_settings" action="<?php print_unescaped($_['action_url']) ?>" method="post">

        <p>
        <label for="new_user_group"><?php p($l->t('Default group that all new users belong')); ?></label>
        <select id="new_user_group" name="new_user_group">
            <option value=""><?php p($l->t('None')); ?></option>
            <?php foreach ($_['groups'] as $group): ?>
                <option value="<?php p($group) ?>" <?php p($_['new_user_group'] === $group ? 'selected' : '') ?>><?php p($group) ?></option>
            <?php endforeach ?>
        </select>
        <div>
            <input id="disable_registration" type="checkbox" class="checkbox" name="disable_registration" value="1" <?php p($_['disable_registration'] ? 'checked' : '') ?>/>
            <label for="disable_registration"><?php p($l->t('Disable auto create new users')) ?></label>
        </div>
        <div>
            <input id="allow_login_connect" type="checkbox" class="checkbox" name="allow_login_connect" value="1" <?php p($_['allow_login_connect'] ? 'checked' : '') ?>/>
            <label for="allow_login_connect"><?php p($l->t('Allow users to connect social logins with their account')) ?></label>
        </div>
        <div>
            <input id="prevent_create_email_exists" type="checkbox" class="checkbox" name="prevent_create_email_exists" value="1" <?php p($_['prevent_create_email_exists'] ? 'checked' : '') ?>/>
            <label for="prevent_create_email_exists"><?php p($l->t('Prevent creating an account if the email address exists in another account')) ?></label>
        </div>
        </p>
        <button><?php p($l->t('Save')); ?></button>
        <hr/>
        <?php foreach ($_['providers'] as $name => $provider): ?>
            <div class="provider-settings">
                <h2 class="provider-title"><img src="<?php print_unescaped(image_path('sociallogin', strtolower($name).'.svg')); ?>" />  <?php p(ucfirst($name))?></h2>
                <label>
                    <?php p($l->t('App id')) ?><br>
                    <input type="text" name="providers[<?php p($name) ?>][appid]" value="<?php p($provider['appid']) ?>"/>
                </label>
                <br/>
                <label>
                    <?php p($l->t('Secret')) ?><br>
                    <input type="password" name="providers[<?php p($name) ?>][secret]" value="<?php p($provider['secret']) ?>"/>
                </label>
                <?php if ($name === 'google'): ?>
                    <br/>
                    <label>
                        <?php p($l->t('Allow login only from specified domain')) ?><br>
                        <input type="text" name="providers[<?php p($name) ?>][auth_params][hd]" value="<?php p(isset($provider['auth_params']['hd']) ? $provider['auth_params']['hd'] : '') ?>"/>
                    </label>
                <?php endif ?>
            </div>
        <?php endforeach ?>
        <br/>
        <div class="provider-settings">
            <h2 class="provider-title"><img src="<?php print_unescaped(image_path('sociallogin', 'telegram.svg')); ?>" /> Telegram</h2>
            <label>
                <?php p($l->t('Bot login')) ?><br>
                <input type="text" name="tg_bot" value="<?php p($_['tg_bot']) ?>"/>
            </label>
            <br/>
            <label>
                <?php p($l->t('Token')) ?><br>
                <input type="password" name="tg_token" value="<?php p($_['tg_token']) ?>"/>
            </label>
        </div>
        <br/>

        <?php foreach ($providersData as $provType => $provData): ?>
        <h2>
            <?php p($l->t($provData['title'])) ?>
            <button id="<?php p($provType)?>_add" type="button">
                <div class="icon-add"></div>
            </button>
        </h2>
        <div id="<?php p($provType)?>_providers">
        <?php foreach ($_[$provType.'_providers'] as $k => $provider): ?>
            <div class="provider-settings">
                <div class="<?php p($provType)?>-remove">x</div>
                <?php foreach ($provData['fields'] as $fieldName => $fieldData): ?>
                    <label>
                        <?php p($l->t($fieldData['title'])) ?><br>
                        <input
                            type="<?php p($fieldData['type'])?>"
                            name="<?php p($provType)?>_providers[<?php p($k) ?>][<?php p($fieldName)?>]"
                            value="<?php p($provider[$fieldName]) ?>"
                            <?php p($fieldName == 'name' ? 'readonly' : ($fieldData['required'] ? 'required' : '' )) ?>
                        />
                    </label>
                    <br/>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>
        </div>
        <br/>
        <?php endforeach ?>

        <button><?php p($l->t('Save')); ?></button>
    </form>

<?php foreach ($providersData as $provType => $provData): ?>
    <div id="<?php p($provType) ?>_provider_tpl" class="provider-settings" data-new-id="<?php p(count($_[$provType.'_providers'])) ?>">
        <div class="<?php p($provType) ?>-remove">x</div>
        <?php foreach ($provData['fields'] as $fieldName => $fieldData): ?>
        <label>
            <?php p($l->t($fieldData['title'])) ?><br>
            <input
                type="<?php p($fieldData['type'])?>"
                name="<?php p($provType) ?>_providers[{{provider_id}}][<?php p($fieldName) ?>]"
                <?php p($fieldData['required'] ? 'required' : '' ) ?>
            />
        </label>
        <br/>
        <?php endforeach ?>
    </div>
<?php endforeach ?>

</div>
