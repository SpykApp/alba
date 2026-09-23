<?= $view->render("partials_head", get_defined_vars()) ?>
<p class="alba-lead"><?= $e($message) ?></p>
<?php if ($license): ?><p class="alba-lead"><?= $e($t('finish.edition')) ?> <strong><?= $e($license['type']) ?></strong></p><?php endif ?>
<form method="post"><input type="hidden" name="_token" value="<?= $e($token) ?>">
  <div class="alba-actions"><button class="alba-btn"><?= $e($t('ui.finish_open')) ?></button></div>
</form>
