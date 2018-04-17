<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div class="section">
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
</div>
