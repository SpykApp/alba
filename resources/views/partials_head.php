<header class="alba-head">
  <h1><?= $e($step->heading()) ?></h1>
  <?php if ($step->subheading() !== ''): ?><p><?= $e($step->subheading()) ?></p><?php endif ?>
</header>
<?php if ($step->instructions()): ?><div class="alba-instructions"><?= $step->instructions() ?></div><?php endif ?>
<?php if (! empty($notice)): ?><div class="alba-alert is-ok"><?= $e($notice) ?></div><?php endif ?>
<?php if (! empty($errors['_'])): ?><div class="alba-alert is-bad"><?= $e($errors['_']) ?></div><?php endif ?>
