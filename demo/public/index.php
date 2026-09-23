<?php

use SpykraLabs\Alba\Http\Request;

require __DIR__.'/../../vendor/autoload.php';

$alba = require __DIR__.'/../alba.php';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/install' || str_starts_with($path, '/install/')) {
    $alba->run();
    exit;
}

$installed = is_file(__DIR__.'/../storage/alba/installed.lock');
?>
<!doctype html><meta charset="utf-8"><title>Demo App</title>
<body style="font:16px system-ui;padding:3rem;max-width:40rem">
<?php if ($installed): ?>
  <h1>Demo App is installed</h1>
  <p>Edition: <strong><?= htmlspecialchars(file_exists(__DIR__.'/../app/Edition/reports/reports.txt') ? 'PRO' : 'LITE') ?></strong></p>
<?php else: ?>
  <h1>Demo App</h1><p>Not installed yet. <a href="/install">Run the installer</a>.</p>
<?php endif ?>
</body>
