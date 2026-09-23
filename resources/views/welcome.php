<?= $view->render("partials_head", get_defined_vars()) ?>
<p class="alba-lead"><?= $e($intro) ?></p>
<ul class="alba-list">
<?php foreach ($steps as $s): if ($s->key() === 'welcome') { continue; } ?>
  <li><strong><?= $e($s->title()) ?></strong><span><?= $e($s->description()) ?></span></li>
<?php endforeach ?>
</ul>
<form method="post"><input type="hidden" name="_token" value="<?= $e($token) ?>">
  <div class="alba-actions"><button class="alba-btn"><?= $e($t('ui.begin')) ?></button></div>
</form>
