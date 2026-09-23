<?php $url = $buttonUrl ?? $alba->redirectTo; ?>
<header class="alba-head"><h1><?= $e($title) ?></h1></header>
<p class="alba-lead"><?= $e($message) ?></p>
<?php if ($url !== '' && $buttonLabel !== ''): ?><div class="alba-actions"><a class="alba-btn" href="<?= $e($url) ?>"><?= $e($buttonLabel) ?></a></div><?php endif ?>
