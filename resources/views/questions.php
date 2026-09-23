<?= $view->render("partials_head", get_defined_vars()) ?>
<form method="post"><input type="hidden" name="_token" value="<?= $e($token) ?>">
<?php foreach ($fields as $name => $f):
  $val = $old[$name] ?? $saved[$name] ?? $f['default']; ?>
  <label class="alba-field"><span><?= $e($f['label']) ?></span>
    <?php if ($f['type'] === 'select'): ?>
      <select name="<?= $e($name) ?>"><?php foreach ($f['options'] as $k => $l): ?><option value="<?= $e($k) ?>" <?= (string) $k === (string) $val ? 'selected' : '' ?>><?= $e($l) ?></option><?php endforeach ?></select>
    <?php elseif ($f['type'] === 'textarea'): ?>
      <textarea name="<?= $e($name) ?>" rows="3"><?= $e($val) ?></textarea>
    <?php else: ?>
      <input type="<?= $e($f['type'] === 'number' ? 'text' : $f['type']) ?>" name="<?= $e($name) ?>" value="<?= $f['type'] === 'password' ? '' : $e($val) ?>" autocomplete="<?= $f['type'] === 'password' ? 'new-password' : 'off' ?>">
    <?php endif ?>
    <?php if ($f['help']): ?><em><?= $e($f['help']) ?></em><?php endif ?>
    <?php if (isset($errors[$name])): ?><small class="alba-err"><?= $e($errors[$name]) ?></small><?php endif ?>
  </label>
<?php endforeach ?>
  <div class="alba-actions"><button class="alba-btn">Continue</button></div>
</form>
