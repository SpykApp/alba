<?= $view->render("partials_head", get_defined_vars()) ?>
<ul class="alba-checks">
<?php foreach ($checks as $c): ?>
  <li class="<?= $c['ok'] ? 'is-ok' : 'is-bad' ?>">
    <span class="alba-badge"><?= $c['ok'] ? '&#10003;' : '&#10005;' ?></span>
    <span class="alba-check-label"><?= $e($c['label']) ?></span>
    <span class="alba-check-detail"><?= $e($c['detail']) ?></span>
  </li>
<?php endforeach ?>
</ul>
<form method="post"><input type="hidden" name="_token" value="<?= $e($token) ?>">
  <div class="alba-actions">
    <?php if ($passed): ?><button class="alba-btn"><?= $e($t('ui.continue')) ?></button>
    <?php else: ?><a class="alba-btn is-ghost" href=""><?= $e($t('ui.recheck')) ?></a><?php endif ?>
  </div>
</form>
