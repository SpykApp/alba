<?php
$theme = $alba->theme;
$base = rtrim($alba->route, '/').'/_alba';
$title = $theme['title'] ?? $alba->name;
$keys = array_keys($steps);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= $e($title) ?> - Installer</title>
<script>try{var t=localStorage.getItem('alba-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
<link rel="stylesheet" href="<?= $e($base) ?>/alba.css">
<?php if ($hasThemeCss): ?><link rel="stylesheet" href="<?= $e($base) ?>/theme.css"><?php endif ?>
<?php if ($tokensCss): ?><style><?= $tokensCss ?></style><?php endif ?>
<?php if ($hasCustomCss): ?><link rel="stylesheet" href="<?= $e($base) ?>/custom.css"><?php endif ?>
</head>
<body data-token="<?= $e($token) ?>">
<div class="alba">
  <aside class="alba-side">
    <div class="alba-brand">
      <?php if (is_array($logo)): ?>
        <img class="alba-logo alba-logo-light" src="<?= $e($logo['light'] ?? $logo['dark'] ?? '') ?>" alt="<?= $e($title) ?>">
        <img class="alba-logo alba-logo-dark" src="<?= $e($logo['dark'] ?? $logo['light'] ?? '') ?>" alt="<?= $e($title) ?>">
      <?php elseif ($logo): ?><img class="alba-logo" src="<?= $e($logo) ?>" alt="<?= $e($title) ?>"><?php endif ?>
      <span><?= $e($title) ?></span>
    </div>
    <ol class="alba-steps">
      <?php foreach ($steps as $key => $s):
        $isDone = in_array($key, $done, true);
        $cls = $key === $active ? 'is-active' : ($isDone ? 'is-done' : ''); ?>
        <li class="<?= $cls ?>">
          <span class="alba-dot"><?= $isDone && $key !== $active ? '&#10003;' : array_search($key, $keys) + 1 ?></span>
          <span><?= $e($s->title()) ?></span>
        </li>
      <?php endforeach ?>
    </ol>
    <button type="button" class="alba-toggle" data-theme-toggle>Toggle theme</button>
  </aside>
  <main class="alba-main">
    <?= $content ?>
    <?php if ($alba->poweredBy): ?><p class="alba-foot"><?php if ($alba->poweredBy['url']): ?><a href="<?= $e($alba->poweredBy['url']) ?>" target="_blank" rel="noopener"><?= $e($alba->poweredBy['text']) ?></a><?php else: ?><?= $e($alba->poweredBy['text']) ?><?php endif ?></p><?php endif ?>
  </main>
</div>
<script src="<?= $e($base) ?>/alba.js"></script>
</body>
</html>
