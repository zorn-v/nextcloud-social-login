<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div id="sociallogin" class="section">
	<form id="sociallogin_settings" action="<?php print_unescaped($_['action_url']) ?>" method="post">
		<p>
		<label for="new_user_group"><?php p($l->t('Default group that all new users belong')); ?></label>
		<select id="new_user_group" name="new_user_group">
			<option><?php p($l->t('None')); ?></option>
			<?php foreach ( $_['groups'] as $group ): ?>
				<option value="<?php p($group) ?>" <?php p($_['new_user_group'] === $group ? 'selected' : '') ?>><?php p($group) ?></option>
			<?php endforeach ?>
		</select>
		</p>
		<p>
			<h2><?php p('Facebook')?></h2>
			<label>
				<?php p('App id') ?><br>
				<input type="text" name="facebook_appid" value="<?php p($_['facebook_appid']) ?>">
			</label>
			<br>
			<label>
				<?php p('Secret') ?><br>
				<input type="password" name="facebook_secret" value="<?php p($_['facebook_secret']) ?>">
			</label>
		</p>
		<br/>
		<p>
			<h2><?php p('Google')?></h2>
			<label>
				<?php p('App id') ?><br>
				<input type="text" name="google_appid" value="<?php p($_['google_appid']) ?>">
			</label>
			<br>
			<label>
				<?php p('Secret') ?><br>
				<input type="password" name="google_secret" value="<?php p($_['google_secret']) ?>">
			</label>
		</p>

		<button><?php p($l->t('Save')); ?></button>
	</form>
</div>
