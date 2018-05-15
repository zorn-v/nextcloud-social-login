<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div class="section sociallogin-connect">
    <div>
        <input id="disable_password_confirmation" type="checkbox" class="checkbox password-confirm-required" name="disable_password_confirmation" value="1" <?php p($_['disable_password_confirmation'] ? 'checked' : '') ?>/>
        <label for="disable_password_confirmation"><?php p($l->t('Disable password confirmation on settings change')) ?></label>
    </div>
    <?php if ($_['allow_login_connect']): ?>
    <h2><?php p($l->t('Social login connect')); ?></h2>
    <ul>
        <?php foreach ($_['connected_logins'] as $title=>$url): ?>
        <li><a href="<?php print_unescaped($url) ?>"><?php p($title) ?></a></li>
        <?php endforeach ?>
    </ul>
    <h3><?php p($l->t('Available providers')) ?></h3>
    <ul>
        <?php foreach ($_['providers'] as $title=>$url): ?>
        <li><a href="<?php print_unescaped($url) ?>"><?php p($title) ?></a></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
