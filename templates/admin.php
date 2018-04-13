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
		<?php foreach ($_['providers'] as $title=>$provider): ?>
			<p>
				<h2><?php p(ucfirst($title))?></h2>
				<?php if (isset($provider['enabled'])): ?>
					<input id="<?php p($title)?>_enabled" class="checkbox" type="checkbox" <?php p($provider['enabled'] ? 'checked' : '') ?> name="openid_providers[]" value="<?php p($title) ?>" />
	            	<label for="<?php p($title)?>_enabled"><?php p($l->t('Enabled')) ?></label>
				<?php else: ?>
				<label>
					<?php p('App id') ?><br>
					<input type="text" name="providers[<?php p($title) ?>][appid]" value="<?php p($provider['appid']) ?>"/>
				</label>
				<br>
				<label>
					<?php p('Secret') ?><br>
					<input type="password" name="providers[<?php p($title) ?>][secret]" value="<?php p($provider['secret']) ?>"/>
				</label>
				<?php endif ?>
			</p>
			<br/>
		<?php endforeach ?>

		<button><?php p($l->t('Save')); ?></button>
	</form>
</div>
