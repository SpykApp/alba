<?php
$v = fn ($k, $d = '') => $old[$k] ?? $saved[$k] ?? $d;
$driver = $v('driver', $drivers[0]);
$err = fn ($k) => isset($errors[$k]) ? '<small class="alba-err">'.$e($errors[$k]).'</small>' : '';
$labels = ['mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite'];
?>
<?= $view->render("partials_head", get_defined_vars()) ?>
<form method="post" data-db-form data-sqlite-default="<?= $e($sqliteDefault) ?>" data-label-file="<?= $e($t('db.file')) ?>" data-label-name="<?= $e($t('db.name')) ?>"><input type="hidden" name="_token" value="<?= $e($token) ?>">
  <label class="alba-field"><span><?= $e($t('db.driver')) ?></span>
    <select name="driver" data-driver>
      <?php foreach ($drivers as $d): ?><option value="<?= $e($d) ?>" <?= $d === $driver ? 'selected' : '' ?>><?= $e($labels[$d] ?? $d) ?></option><?php endforeach ?>
    </select><?= $err('driver') ?></label>
  <div class="alba-grid" data-net>
    <label class="alba-field"><span><?= $e($t('db.host')) ?></span><input name="host" value="<?= $e($v('host', '127.0.0.1')) ?>"><?= $err('host') ?></label>
    <label class="alba-field"><span><?= $e($t('db.port')) ?></span><input name="port" value="<?= $e($v('port', '3306')) ?>"><?= $err('port') ?></label>
  </div>
  <label class="alba-field"><span data-db-label><?= $e($t($driver === 'sqlite' ? 'db.file' : 'db.name')) ?></span>
    <input name="database" value="<?= $e($v('database', $driver === 'sqlite' ? $sqliteDefault : '')) ?>"><?= $err('database') ?></label>
  <div class="alba-grid" data-net>
    <label class="alba-field"><span><?= $e($t('db.username')) ?></span><input name="username" value="<?= $e($v('username')) ?>" autocomplete="off"><?= $err('username') ?></label>
    <label class="alba-field"><span><?= $e($t('db.password')) ?></span><input type="password" name="password" autocomplete="new-password"></label>
  </div>
  <div class="alba-actions"><button class="alba-btn"><?= $e($t('db.submit')) ?></button></div>
</form>
