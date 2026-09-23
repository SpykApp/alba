<?php
$url = $buttonUrl ?? $alba->redirectTo;
$heading = $title !== null ? $text($title) : $t('installed.title');
$body = $message !== null ? $text($message) : $t('installed.message');
$label = $buttonLabel !== null ? $text($buttonLabel) : $t('installed.button');
?>
<header class="alba-head"><h1><?= $e($heading) ?></h1></header>
<p class="alba-lead"><?= $e($body) ?></p>
<?php if ($url !== '' && $label !== ''): ?><div class="alba-actions"><a class="alba-btn" href="<?= $e($url) ?>"><?= $e($label) ?></a></div><?php endif ?>
