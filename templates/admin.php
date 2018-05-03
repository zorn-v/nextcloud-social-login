<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
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
		</p>
		<hr/>
		<?php foreach ($_['providers'] as $name=>$provider): ?>
			<div class="provider-settings">
				<h2><?php p(ucfirst($name))?></h2>
				<label>
					<?php p('App id') ?><br>
					<input type="text" name="providers[<?php p($name) ?>][appid]" value="<?php p($provider['appid']) ?>"/>
				</label>
				<br/>
				<label>
					<?php p('Secret') ?><br>
					<input type="password" name="providers[<?php p($name) ?>][secret]" value="<?php p($provider['secret']) ?>"/>
				</label>
			</div>
		<?php endforeach ?>
		<br/>
		<h2>
			OpenID
			<button id="openid_add" type="button">
				<div class="icon-add"></div>
			</button>
		</h2>
		<div id="openid_providers">
		<?php foreach ($_['openid_providers'] as $k=>$provider): ?>
			<div class="provider-settings">
				<div class="openid-remove">x</div>
				<label>
					<?php p('Internal name') ?><br>
					<input type="text" name="openid_providers[<?php p($k) ?>][name]" value="<?php p($provider['name']) ?>" class="disabled" readonly/>
				</label>
				<br/>
				<label>
					<?php p('Title') ?><br>
					<input type="text" name="openid_providers[<?php p($k) ?>][title]" value="<?php p($provider['title']) ?>" required/>
				</label>
				<br/>
				<label>
					<?php p('Identifier url') ?><br>
					<input type="url" name="openid_providers[<?php p($k) ?>][url]" value="<?php p($provider['url']) ?>" required/>
				</label>
			</div>
		<?php endforeach ?>
		</div>
		<br/>
    	<h2>
			Custom OpenID Connect
			<button id="custom_oidc_add" type="button">
				<div class="icon-add"></div>
			</button>
		</h2>
		<div id="custom_oidc_providers">
		<?php foreach ($_['custom_oidc_providers'] as $k=>$provider): ?>
			<div class="provider-settings">
				<div class="custom_oidc-remove">x</div>
				<label>
					<?php p('Internal name') ?><br>
					<input type="text" name="custom_oidc_providers[<?php p($k) ?>][name]" value="<?php p($provider['name']) ?>" readonly/>
				</label>
				<br/>
				<label>
					<?php p('Title') ?><br>
					<input type="text" name="custom_oidc_providers[<?php p($k) ?>][title]" value="<?php p($provider['title']) ?>" required/>
				</label>
				<br/>
        		<label>
					<?php p('Authorize url') ?><br>
					<input type="url" name="custom_oidc_providers[<?php p($k) ?>][authorizeUrl]" value="<?php p($provider['authorizeUrl']) ?>" required/>
				</label>
		        <br/>
		        <label>
					<?php p('Token url') ?><br>
					<input type="url" name="custom_oidc_providers[<?php p($k) ?>][tokenUrl]" value="<?php p($provider['tokenUrl']) ?>" required/>
		        </label>
		        <br/>
		        <label>
					<?php p('Client Id') ?><br>
					<input type="text" name="custom_oidc_providers[<?php p($k) ?>][clientId]" value="<?php p($provider['clientId']) ?>" required/>
		        </label>
		        <br/>
		        <label>
					<?php p('Client Secret') ?><br>
					<input type="text" name="custom_oidc_providers[<?php p($k) ?>][clientSecret]" value="<?php p($provider['clientSecret']) ?>" required/>
		        </label>
		        <br/>
		        <label>
					<?php p('Scope') ?><br>
					<input type="text" name="custom_oidc_providers[<?php p($k) ?>][scope]" value="<?php p($provider['scope']) ?>" required/>
		        </label>
			</div>
		<?php endforeach ?>
		</div>
		<br/>
		<button><?php p($l->t('Save')); ?></button>
	</form>

  	<div id="openid_provider_tpl" class="provider-settings" data-new-id="<?php p(count($_['openid_providers'])) ?>">
		<div class="openid-remove">x</div>
		<label>
			<?php p('Internal name') ?><br>
			<input type="text" name="openid_providers[{{provider_id}}][name]" required/>
		</label>
		<br/>
		<label>
			<?php p('Title') ?><br>
			<input type="text" name="openid_providers[{{provider_id}}][title]" required/>
		</label>
		<br/>
		<label>
			<?php p('Identifier url') ?><br>
			<input type="url" name="openid_providers[{{provider_id}}][url]" required/>
		</label>
	</div>

  	<div id="custom_oidc_provider_tpl" class="provider-settings" data-new-id="<?php p(count($_['custom_oidc_providers'])) ?>">
		<div class="custom_oidc-remove">x</div>
		<label>
			<?php p('Internal name') ?><br>
			<input type="text" name="custom_oidc_providers[{{provider_id}}][name]" required/>
		</label>
		<br/>
	    <label>
			<?php p('Title') ?><br>
			<input type="text" name="custom_oidc_providers[{{provider_id}}][title]" required/>
		</label>
		<br/>
	    <label>
			<?php p('Authorize URL') ?><br>
			<input type="url" name="custom_oidc_providers[{{provider_id}}][authorizeUrl]" required/>
		</label>
		<br/>
    	<label>
			<?php p('Token URL') ?><br>
			<input type="url" name="custom_oidc_providers[{{provider_id}}][tokenUrl]" required/>
		</label>
		<br/>
    	<label>
			<?php p('Client Id') ?><br>
			<input type="text" name="custom_oidc_providers[{{provider_id}}][clientId]" required/>
		</label>
		<br/>
    	<label>
			<?php p('Client Secret') ?><br>
			<input type="text" name="custom_oidc_providers[{{provider_id}}][clientSecret]" required/>
		</label>
		<br/>
    	<label>
			<?php p('Scope') ?><br>
			<input type="text" name="custom_oidc_providers[{{provider_id}}][scope]" required/>
		</label>
		<br/>
	</div>

</div>
