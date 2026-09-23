<?php $err = fn ($k) => isset($errors[$k]) ? '<small class="alba-err">'.$e($errors[$k]).'</small>' : ''; ?>
<?= $view->render("partials_head", get_defined_vars()) ?>
<?php if ($license): ?><div class="alba-alert is-ok"><?= $e($t('license.verified_banner')) ?> <strong><?= $e($license['type']) ?></strong></div><?php endif ?>
<form method="post"><input type="hidden" name="_token" value="<?= $e($token) ?>">
  <label class="alba-field"><span><?= $e($codeLabel) ?></span>
    <input name="code" value="<?= $e($old['code'] ?? '') ?>" autocomplete="off" spellcheck="false" autofocus><?= $err('code') ?></label>
  <?php foreach ($extraFields as $f): ?>
    <label class="alba-field"><span><?= $e(ucfirst(str_replace('_', ' ', $f))) ?></span><input name="<?= $e($f) ?>" value="<?= $e($old[$f] ?? '') ?>"></label>
  <?php endforeach ?>
  <div class="alba-actions"><button class="alba-btn"><?= $e($t('license.verify')) ?></button></div>
</form>
