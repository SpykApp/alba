<?= $view->render("partials_head", get_defined_vars()) ?>
<form method="post" data-tasks data-run-url="<?= $e($ctx->url($step->key())) ?>/run" data-retry="<?= $e($t('ui.retry')) ?>" data-failed="<?= $e($t('ui.request_failed', ['message' => '%s'])) ?>"><input type="hidden" name="_token" value="<?= $e($token) ?>">
  <ul class="alba-tasks">
  <?php foreach ($tasks as $i => $t):
    $r = $results[$i] ?? null;
    $state = $r === null ? 'pending' : ($r['ok'] ? 'ok' : 'bad'); ?>
    <li data-task="<?= $i ?>" data-state="<?= $state ?>">
      <span class="alba-badge"></span>
      <span class="alba-check-label"><?= $e($t->name()) ?></span>
      <pre class="alba-log" <?= $r ? '' : 'hidden' ?>><?= $e($r['log'] ?? '') ?></pre>
    </li>
  <?php endforeach ?>
  </ul>
  <div class="alba-actions">
    <button class="alba-btn" data-run><?= $e($button) ?></button>
  </div>
</form>
