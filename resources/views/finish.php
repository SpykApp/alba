<?= $view->render("partials_head", get_defined_vars()) ?>
<p class="alba-lead"><?= $e($message) ?></p>
<?php if ($license): ?><p class="alba-lead">Edition installed: <strong><?= $e($license['type']) ?></strong></p><?php endif ?>
<form method="post"><input type="hidden" name="_token" value="<?= $e($token) ?>">
  <div class="alba-actions"><button class="alba-btn">Finish &amp; open the app</button></div>
</form>
