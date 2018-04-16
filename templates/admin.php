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
		</p>
		<hr/>
		<?php foreach ($_['providers'] as $title=>$provider): ?>
			<div class="provider-settings">
				<h2><?php p(ucfirst($title))?></h2>
				<label>
					<?php p('App id') ?><br>
					<input type="text" name="providers[<?php p($title) ?>][appid]" value="<?php p($provider['appid']) ?>"/>
				</label>
				<br>
				<label>
					<?php p('Secret') ?><br>
					<input type="password" name="providers[<?php p($title) ?>][secret]" value="<?php p($provider['secret']) ?>"/>
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
					<?php p('Title') ?><br>
					<input type="text" name="openid_providers[<?php p($k) ?>][title]" value="<?php p($provider['title']) ?>" required/>
				</label>
				<br>
				<label>
					<?php p('Identifier url') ?><br>
					<input type="url" name="openid_providers[<?php p($k) ?>][url]" value="<?php p($provider['url']) ?>" required/>
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
			<?php p('Title') ?><br>
			<input type="text" name="openid_providers[{{provider_id}}][title]" required/>
		</label>
		<br>
		<label>
			<?php p('Identifier url') ?><br>
			<input type="url" name="openid_providers[{{provider_id}}][url]" required/>
		</label>
	</div>
</div>
