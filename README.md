# Alba - PHP App Installer

Alba is a free, themable installation wizard for PHP applications. You describe the steps in PHP, Alba serves a clean installer at a route you choose (`/install`, `/system/install`, anything), and your users get a guided setup: server checks, folder permissions, database, licence verification, custom questions, migrations, seeding and post-install commands.

It has **no required dependencies**, needs **no Node or build step**, and works with **any PHP app**: Laravel, Symfony, Slim, CodeIgniter, Yii, Laminas, WordPress-style projects, or plain PHP.

- Vendor: SpykraLabs
- Package: `spykralabs/alba`
- Namespace: `SpykraLabs\Alba`
- PHP: 8.1 or newer
- License: MIT

Throughout this document, the **developer** is you (the person shipping an app with Alba) and the **user** is the person running your installer.

---

## Contents

1. [Features](#features)
2. [Installation](#installation)
3. [Quick start (plain PHP)](#quick-start-plain-php)
4. [How it works](#how-it-works)
5. [Framework integration](#framework-integration)
6. [Web server configuration](#web-server-configuration)
7. [Configuration reference (`Alba`)](#configuration-reference-alba)
8. [Steps](#steps)
9. [Licence verification and file actions](#licence-verification-and-file-actions)
10. [Framework presets](#framework-presets)
11. [Tasks: migrate, seed, commands](#tasks-migrate-seed-commands)
12. [Writing env and config files](#writing-env-and-config-files)
13. [After installation: who can open the installer?](#after-installation-who-can-open-the-installer)
14. [Access control (guard)](#access-control-guard)
15. [Branding, headings and instructions](#branding-headings-and-instructions)
16. [Theming](#theming)
17. [Custom steps](#custom-steps)
18. [The Context object](#the-context-object)
19. [State, lock file and `.env`](#state-lock-file-and-env)
20. [Security](#security)
21. [Demo](#demo)
22. [Troubleshooting](#troubleshooting)
23. [Project status](#project-status)
24. [Contributing](#contributing)
25. [License](#license)

---

## Features

| Capability | What it does |
|---|---|
| Server requirements | Checks the PHP version, extensions, ini values (such as `memory_limit`) and required functions. |
| Folder permissions | Checks that folders and files are writable. Missing paths only need a writable parent. |
| Database setup | MySQL/MariaDB, PostgreSQL and SQLite. Tests the connection live, then writes the credentials to your `.env`. |
| Licence check | Envato purchase codes, or any custom verification (closure or your own class). |
| Licence based files | Copy or delete files and folders depending on the licence type (for example `pro` and `lite` editions). |
| Custom questions | Ask the user anything: text, email, password, URL, number, select, textarea. With validation and optional `.env` mapping. |
| Framework presets | Laravel, Symfony, CodeIgniter 4, Yii 2, CakePHP, WordPress, Drupal, Phinx and Doctrine Migrations: writes the framework's own database config, runs its migrations and seeders, and its post-install commands. |
| Migrations and seeding | Run framework commands, SQL files or your own PHP, with per-task progress and logs. |
| Env and config files | Write `.env` values and PHP, INI or any text config files from templates with placeholders and random keys. |
| Post-install commands | Run `php artisan storage:link`, cache warmers, or any callable. |
| Configurable steps | You decide which steps exist, their order, titles, headings and instructions. |
| Configurable route | Serve the installer at `/install`, `/system/install` or any path. |
| Installed behaviour | After installation show a page, return an HTTP status, redirect, or keep the wizard available. |
| Theming | Dark and light mode, logos for each mode, CSS variables, theme packs (installable or folder based), template overrides. |
| Custom branding | Your logo, your own "powered by" line (or none). |
| Framework agnostic | Own tiny request and response objects. Adapters for plain PHP, PSR-15 and Laravel. |

---

## Installation

```bash
composer require spykralabs/alba
```

If you are using the package before it is published to Packagist, point Composer at the repository or a local path:

```json
{
    "repositories": [
        { "type": "path", "url": "../alba" }
    ],
    "require": {
        "spykralabs/alba": "*"
    }
}
```

Alba only requires `ext-pdo` and `ext-json`. The Envato verifier also needs `ext-curl`. The PSR-15 adapter needs `psr/http-server-middleware` and a PSR-17 response factory (not installed automatically).

---

## Quick start (plain PHP)

Create `alba.php` in your project root. It returns the configured installer:

```php
<?php

use SpykraLabs\Alba\Alba;
use SpykraLabs\Alba\Steps\{Welcome, Requirements, Permissions, Database, Finish};

return Alba::configure('My App')
    ->route('/install')
    ->basePath(__DIR__)
    ->redirectTo('/')
    ->steps([
        Welcome::make(),
        Requirements::make()->php('8.1.0')->extensions(['pdo', 'mbstring', 'openssl']),
        Permissions::make()->writable(['storage', '.env']),
        Database::make()->drivers(['mysql', 'sqlite']),
        Finish::make(),
    ]);
```

Create a front controller (for example `public/index.php`) that sends installer requests to Alba:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$alba = require __DIR__ . '/../alba.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/install' || str_starts_with($path, '/install/')) {
    $alba->run();   // reads PHP globals, sends the response
    exit;
}

// ... the rest of your application
```

Open `/install` in a browser. Alba redirects to the first incomplete step.

To try it locally without any setup, run the [demo](#demo).

---

## How it works

**Request lifecycle**

1. A request arrives at your route (for example `/install/database`). An adapter converts it into an Alba `Request` and calls `Alba::handle()`, which returns a `Response`.
2. Alba serves its own assets under `{route}/_alba/`.
3. If the app is already installed, the [installed behaviour](#after-installation-who-can-open-the-installer) is applied.
4. Otherwise the wizard routes `GET {route}/{step}` to show a step and `POST {route}/{step}` to submit it.
5. A step can only be opened once every earlier step is complete. Completed steps can be revisited. Opening `{route}` itself redirects to the first incomplete step, so users can resume where they left off.
6. When a step succeeds it is marked complete and the user is redirected to the next one.
7. Task steps (migrate, seed, commands) run one task at a time through small background requests, so the user sees live progress and the exact failure if something goes wrong. Without JavaScript, submitting the form runs the remaining tasks in one go.
8. The `Finish` step writes the lock file, deletes the saved state and redirects to `redirectTo`.

**Steps are objects.** You choose how many exist, and in what order. Every built-in step can be used, skipped, reordered or replaced by your own.

---

## Framework integration

The core is framework independent. Adapters only translate the framework's request and response.

### Plain PHP and any other framework

Use `$alba->run()` from a front controller or from a route that Alba owns (see the quick start). This works everywhere, including frameworks that have no adapter, as long as you can route `/install/*` to a script that runs Alba before the framework boots.

`SpykraLabs\Alba\Adapters\PlainPhp::serve($alba)` is an alias for `$alba->run()`.

### Laravel

The package registers `SpykraLabs\Alba\Adapters\LaravelServiceProvider` through package discovery. Create `alba.php` in the project root (same file as the quick start, returning the `Alba` instance). The provider loads it, sets the base path to `base_path()` and mounts a catch-all route at the configured route without any middleware, so the installer works before `.env` or the database exist.

```php
// alba.php in the Laravel project root
$laravel = Laravel::make();

return Alba::configure('My App')
    ->route('/install')
    ->framework($laravel)
    ->steps([
        Welcome::make(),
        Requirements::make()->forFramework($laravel),
        Permissions::make()->forFramework($laravel),
        Database::make()->drivers(['mysql', 'pgsql', 'sqlite']),
        TaskStep::migrate()->using($laravel),
        TaskStep::seed()->using($laravel),
        TaskStep::commands()->using($laravel),
        Finish::make(),
    ]);
```

Notes for Laravel:

- Alba writes the `DB_*` keys to `.env` (creating it if needed) and generates `APP_KEY` in the finish tasks. Add `$laravel->copyEnvExample()` as an early task if you want the other defaults from `.env.example`.
- If your config is cached (`php artisan config:cache`), clear it before installation.
- Alba's own state is not stored in the Laravel session or database.

### Symfony, Slim, Mezzio, Laminas (PSR-15)

```php
use SpykraLabs\Alba\Adapters\Psr15Middleware;

$alba = require __DIR__ . '/alba.php';

$app->add(new Psr15Middleware($alba, $psr17ResponseFactory));
```

Requests under the Alba route are answered by Alba. Everything else passes through to the next handler. Install `psr/http-server-middleware` and a PSR-7 implementation yourself.

---

## Web server configuration

Alba serves all its pages through PHP, so the installer route only needs to reach your front controller.

**Apache** (`.htaccess`):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

**Nginx**:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Built-in PHP server** (development):

```bash
php -S localhost:8088 -t public
```

Keep Alba's storage directory (`storage/alba` by default) out of the public web root if you can. See [Security](#security).

---

## Configuration reference (`Alba`)

`Alba::configure(string $name)` returns a fluent builder. Every method returns the builder.

| Method | Default | Description |
|---|---|---|
| `route(string $route)` | `/install` | URL path of the installer, for example `/system/install`. |
| `basePath(string $path)` | current directory | Your application root. Relative paths in steps, tasks and file actions resolve against it. Also resets `storagePath` to `{basePath}/storage/alba`. |
| `envFile(?string $file)` | `.env` | File (relative to `basePath`) that receives database and question values. Pass `null` to never write an env file. |
| `storagePath(string $path)` | `{basePath}/storage/alba` | Where Alba keeps its state file and lock file. Call it after `basePath()`. |
| `redirectTo(string $url)` | `/` | Where the Finish step sends the user, and the default target of the "Open the app" button. |
| `steps(array $steps)` | none | The ordered list of steps. |
| `theme(array\|Theme $theme)` | default theme | A theme pack, or an array with `title`, `logo`, `brand`, `radius`, `font`. |
| `poweredBy(string\|false $text, ?string $url)` | Alba credit | The footer line. `false` hides it. |
| `extraCss(string $path)` | none | Absolute path to a CSS file loaded after everything else. |
| `viewsPath(string $path)` | none | A folder of template overrides. Searched before theme and package templates. |
| `guard(Closure $guard)` | none | Return `false` to block access. See [Access control](#access-control-guard). |
| `whenInstalled(InstalledBehavior $b)` | `page()` | What visitors see after installation. |
| `handle(Request $request): Response` | | Handle a request and return a response (for adapters and tests). |
| `run(): void` | | Plain PHP entry point: read globals, send the response. |

---

## Steps

All built-in steps live in `SpykraLabs\Alba\Steps`. Every step is created with `Step::make()` (task steps use their own factories) and shares these methods:

| Method | Description |
|---|---|
| `withTitle(string $title, ?string $description)` | Sidebar label and default subheading. |
| `withHeading(string $heading, ?string $subheading)` | Page heading and the text under it. |
| `withInstructions(string $html)` | An instructions box shown under the heading. |
| `withKey(string $key)` | Change the URL segment (and identifier) of the step. |

Step keys must be unique. Defaults: `welcome`, `requirements`, `permissions`, `database`, `license`, `settings`, `migrate`, `seed`, `commands`, `finish`.

### Welcome

```php
Welcome::make()->intro('Thanks for buying My App. This takes about two minutes.');
```

Shows an intro and the list of all upcoming steps.

### Requirements

```php
Requirements::make()
    ->php('8.2.0')
    ->extensions(['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json'])
    ->iniAtLeast('memory_limit', '128M')
    ->iniAtLeast('upload_max_filesize', '16M')
    ->functions(['proc_open', 'curl_init']);
```

- `php(string $minimum)`: minimum PHP version.
- `extensions(array $names)`: extensions that must be loaded.
- `iniAtLeast(string $key, string $minimum)`: byte-size ini values (`K`, `M`, `G` suffixes). `-1` (unlimited) always passes.
- `functions(array $names)`: functions that must exist and not be disabled.

The user can only continue when every check passes. The page can be re-checked after fixing the server.

### Permissions

```php
Permissions::make()->writable(['storage', 'bootstrap/cache', '.env']);
```

Paths are relative to `basePath`. A path that does not exist yet passes if its parent directory is writable.

### Database

```php
Database::make()
    ->drivers(['sqlite', 'mysql', 'pgsql'])
    ->sqliteDefault('storage/app.sqlite');
```

- Supported drivers: `mysql` (MySQL and MariaDB), `pgsql`, `sqlite`. The first listed driver is the default selection.
- The form adapts to the driver: SQLite shows only a file path, the others show host, port, name, username and password.
- The connection is tested before the step can be completed. Connection errors are shown to the user.
- On success the values are stored for later tasks (`$ctx->pdo()`), and written to the env file:

```
DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

For SQLite, `DB_DATABASE` is the absolute path of the file. These key names follow the Laravel convention. If your app uses other names, copy the values in a [task](#tasks-migrate-seed-commands) using `$ctx->state->get('db')`.

### Questions (custom information)

```php
Questions::make()
    ->withTitle('Settings', 'Tell us about your site.')
    ->field('app_name', 'Application name', rules: 'required|max:60', env: 'APP_NAME', default: 'My App')
    ->field('app_url', 'Application URL', type: 'url', rules: 'required|url', env: 'APP_URL')
    ->field('timezone', 'Timezone', type: 'select', options: ['UTC' => 'UTC', 'Europe/London' => 'London'])
    ->field('admin_email', 'Admin email', type: 'email', rules: 'required|email')
    ->field('admin_password', 'Admin password', type: 'password', rules: 'required|min:8', help: 'At least 8 characters.');
```

`field(string $name, string $label, string $type = 'text', string $rules = '', ?string $env = null, string $default = '', ?string $help = null, array $options = [])`

- **Types:** `text`, `email`, `password`, `url`, `number`, `select`, `textarea`.
- **Rules** (pipe separated): `required`, `email`, `url`, `numeric`, `min:n` (characters), `max:n` (characters), `same:otherField`.
- **`env`:** if set, the answer is also written to the env file under that key.
- **Reading answers** later: `$ctx->answer('admin_email')`. Password fields are never pre-filled back into the form.

### License

See [Licence verification and file actions](#licence-verification-and-file-actions).

### Task steps (Migrate, Seed, Commands)

See [Tasks](#tasks-migrate-seed-commands).

### Finish

```php
Finish::make()->message('Your app is ready. Sign in with the admin account you created.');
```

Pressing the button writes the lock file, deletes the saved state and redirects to `redirectTo`.

---

## Licence verification and file actions

The `License` step asks for a licence key, verifies it, then applies file actions that match the licence type.

```php
use SpykraLabs\Alba\Steps\License;
use SpykraLabs\Alba\License\{EnvatoVerifier, LicenseResult};
use SpykraLabs\Alba\Files\{Copy, Delete};

License::make()
    ->codeLabel('Purchase code')
    ->verifier(new EnvatoVerifier(token: $envatoToken, itemId: 12345678))
    ->onType('*',    [Delete::path('app/Legacy')])
    ->onType('pro',  [Copy::from('editions/pro',  'app/Edition')])
    ->onType('lite', [Copy::from('editions/lite', 'app/Edition')]);
```

### Custom verification

Pass a closure. It receives the key and any extra form values. Return a `LicenseResult` or a plain boolean:

```php
->verifier(function (string $code, array $extra) {
    $response = my_http_post('https://licenses.example.com/verify', ['key' => $code]);

    return $response['valid']
        ? LicenseResult::valid($response['edition'], ['customer' => $response['email']])
        : LicenseResult::invalid('That key was not recognised.');
})
```

Or implement `SpykraLabs\Alba\License\LicenseVerifier`:

```php
interface LicenseVerifier
{
    public function verify(string $code, array $extra = []): LicenseResult;
}
```

- `LicenseResult::valid(string $type = 'standard', array $meta = [])`
- `LicenseResult::invalid(string $message)`
- Throw an exception for "could not verify" (network down); the user sees the message.
- `extraField('email')` adds extra inputs (for example an email or username) that are passed to the verifier in `$extra`.

### Envato purchase codes

`new EnvatoVerifier(string $token, ?int $itemId = null, array $licenseTypes = [...], string $endpoint = ...)`

- Uses Envato's author sale API with a personal token that has the "View a sale" permission.
- Checks the code format, then looks the sale up. If `itemId` is set, the code must belong to that item.
- `licenseTypes` maps Envato licence names to your own type names. The default maps `Regular License` to `regular` and `Extended License` to `extended`.
- The result meta contains `buyer`, `sold_at` and `supported_until`.
- Requires `ext-curl`.

Important: a token shipped inside source code can be read by the buyer. For real distribution, verify through a small server that you control and call it with a custom verifier instead of embedding your token.

### File actions

Actions run right after a successful verification, in this order: the `'*'` actions (every licence), then the actions for the licence type.

- `Copy::from(string $from, string $to)`: copies a file or a whole directory recursively. The source is relative to `basePath` (absolute paths also work). Existing files are overwritten.
- `Delete::path(string $path)`: deletes a file or directory recursively. If it does not exist, nothing happens.

Safety: destinations and deletions must be inside `basePath`. Paths containing `..`, or the base path itself, are refused. Failures show as an error on the licence field.

Actions are safe to run again if the user goes back and re-verifies.

### Using the licence later

After verification the result is stored as `$ctx->license()`:

```php
['type' => 'pro', 'meta' => [...], 'log' => ['copied editions/pro -> app/Edition']]
```

Alba deletes its state when installation finishes. If your app needs to remember the licence, save it in a task (for example a `CallbackTask` that writes it to your database).

---

## Framework presets

Migrations and seeders are framework specific, so Alba has **presets**. A preset knows how one framework wants its database configured, what it needs from the server, and which of its own commands migrate, seed and finish an install. Presets run the framework's CLI in a subprocess (for example `php artisan migrate --force`), so Alba never boots your framework and keeps working even when the framework is not configured yet.

### Available presets

All live in `SpykraLabs\Alba\Frameworks`.

| Preset | Detected by | Database config written | Migrate | Seed | Finish tasks |
|---|---|---|---|---|---|
| `Laravel` | `artisan` + `laravel/framework` | `DB_*` keys in `.env` | `artisan migrate --force` (or `migrate:fresh`) | `artisan db:seed --force` (optional `--class`) | generate `APP_KEY`, `storage:link`, `optimize:clear` |
| `Symfony` | `bin/console` + `symfony/framework-bundle` | `DATABASE_URL` in `.env.local` | `doctrine:migrations:migrate` | `doctrine:fixtures:load --append` | `APP_SECRET` + `APP_ENV=prod`, `cache:clear`, `assets:install` |
| `CodeIgniter` | `spark` + `codeigniter4/framework` | `database.default.*` in `.env` | `spark migrate --all` | `spark db:seed` | `encryption.key`, `cache:clear` |
| `Yii2` | `yii` + `yiisoft/yii2` | rewrites `config/db.php` | `yii migrate/up` | `yii fixture/load` | `cache/flush-all` |
| `CakePhp` | `bin/cake` + `cakephp/cakephp` | writes `config/app_local.php` (datasource, fresh salt) | `bin/cake migrations migrate` | `bin/cake migrations seed` | `cache clear_all` |
| `WordPress` | `wp-load.php` / `wp-config-sample.php` | generates `wp-config.php` with fresh salts | `wp core install` (WP-CLI) | none | `wp rewrite flush` |
| `Drupal` | `vendor/bin/drush` + `drupal/core-recommended` | none (passed to Drush as `--db-url`) | `drush site:install` | none | `drush cache:rebuild` |
| `Phinx` | `vendor/bin/phinx` + `phinx.php` | `DB_*` keys in `.env` | `phinx migrate` | `phinx seed:run` | none |
| `DoctrineMigrations` | `vendor/bin/doctrine-migrations` | `DATABASE_URL` in `.env` | `doctrine-migrations migrate` | none | none |

Slim, Mezzio, Laminas and plain PHP projects can use `Phinx` or `DoctrineMigrations`, or their own tasks.

### Using a preset

```php
use SpykraLabs\Alba\Frameworks\Laravel;

$laravel = Laravel::make();

Alba::configure('My App')
    ->framework($laravel)            // or ->framework('laravel'), or ->framework('auto') to detect
    ->steps([
        Welcome::make(),
        Requirements::make()->forFramework($laravel),   // PHP version and extensions the framework needs
        Permissions::make()->forFramework($laravel),    // storage, bootstrap/cache, .env ...
        Database::make()->drivers(['mysql', 'sqlite']), // writes the framework's own config
        TaskStep::migrate()->using($laravel),           // php artisan migrate --force
        TaskStep::seed()->using($laravel),              // php artisan db:seed --force
        TaskStep::commands()->using($laravel),          // key, storage link, cache clear
        Finish::make(),
    ]);
```

- `->framework(...)` makes the **Database** step write the framework's own configuration after the connection test (instead of the generic `DB_*` env keys). If writing fails, the user sees the reason.
- `forFramework()` on `Requirements` and `Permissions` adds the preset's needs to whatever you set yourself.
- `using()` on the migrate, seed and commands steps adds the preset's tasks. You can still call `->add()` for extra tasks.
- `Frameworks::detect($basePath)` and `Frameworks::named('symfony')` are available if you want to pick presets in code.

### Preset options and extra actions

Every preset has methods for individual actions, so you can build a step by hand:

```php
$laravel = Laravel::make()->withPhp('/usr/bin/php8.3');   // CLI binary (recommended under FPM)

TaskStep::migrate()->add($laravel->migrate(fresh: false));
TaskStep::seed()->add($laravel->seed('ProductionSeeder'));
TaskStep::commands()->add(
    $laravel->generateKey(),          // APP_KEY without needing artisan, only if empty
    $laravel->storageLink(),
    $laravel->optimize(),
    $laravel->artisan(['vendor:publish', '--tag=assets', '--force']),   // any artisan command
);
```

| Preset | Extra methods |
|---|---|
| `Laravel` | `copyEnvExample()`, `generateKey()`, `storageLink()`, `clearCaches()`, `optimize()`, `artisan(array $args)` |
| `Symfony` | `createDatabase()`, `updateSchema()`, `generateSecret()`, `clearCache()`, `installAssets()`, `console(array $args)` |
| `CodeIgniter` | `copyEnvTemplate()`, `generateKey()`, `production()`, `clearCache()`, `spark(array $args)` |
| `Yii2` | `migrateRbac()`, `flushCache()`, `yii(array $args)`. Constructor takes the db config path and console script name. |
| `CakePhp` | `clearCache()`, `cake(array $args)` |
| `WordPress` | `coreInstall(array $answerMap)`, `activatePlugins(array $slugs)`. Constructor takes the table prefix and the `wp` binary. |
| `Drupal` | `configImport()`, `updateDatabase()`, `rebuildCache()`, `drush(array $args)`. Constructor takes the drush path and install profile. |
| `Phinx` | `phinx(array $args)`. Constructor takes the environment name and config path. |
| `DoctrineMigrations` | `createSchema()` |

Notes:

- The CLI binary defaults to `php`. Under PHP-FPM `PHP_BINARY` is not the CLI, so use `withPhp()` with a real path if `php` is not on the web user's path.
- Commands need `proc_open` enabled. Add `Requirements::make()->functions(['proc_open'])`.
- WordPress needs WP-CLI (`wp`) and Drupal needs Drush installed on the server for their install tasks. Drupal's `site:install` receives the admin password as a command argument, visible in the process list while it runs.
- CakePHP's `writeDatabase()` replaces `config/app_local.php`. Yii 2's replaces `config/db.php`. WordPress's replaces `wp-config.php`. Keep other local overrides in a separate file.

### Writing your own preset

Extend `SpykraLabs\Alba\Frameworks\Framework` and implement:

```php
final class MyFramework extends Framework
{
    public function name(): string { return 'myframework'; }
    public function detect(string $basePath): bool { return is_file($basePath.'/console'); }
    public function writeDatabase(Context $ctx, array $db): void { /* write config */ }
    public function migrate(): Task { return $this->cli('console', ['db:migrate'], 'Run migrations'); }
    public function seed(): ?Task { return $this->cli('console', ['db:seed'], 'Run seeders'); }
    public function finalize(): array { return []; }
    public function requirements(): array { return ['php' => '8.1.0', 'extensions' => ['pdo'], 'writable' => ['var']]; }
}
```

Helpers available in a preset: `$this->cli($binary, $args, $name)` builds a command task, `$this->writeEnv($ctx, $values, $file)` merges env keys, and `$this->hasComposerPackage($basePath, $package)` helps detection. Presets can be shared as separate Composer packages.

---

## Tasks: migrate, seed, commands

`TaskStep` runs an ordered list of tasks. Three presets exist, or build your own:

```php
TaskStep::migrate();     // key "migrate",  title "Migrate"
TaskStep::seed();        // key "seed",     title "Seed"
TaskStep::commands();    // key "commands", title "Finalize"

TaskStep::named('cache', 'Cache', 'Warm the caches.', 'Warm caches');
```

Add tasks with `->add(Task ...$tasks)`. Each task shows progress, a success or failure badge and its log. A failed task stops the run and the user can retry.

### Built-in tasks

For framework migrations and seeders use a [framework preset](#framework-presets). The tasks below are the generic building blocks.

**`SqlDirectoryTask(string $directory, string $name = 'Run SQL files', string $trackingTable = 'alba_migrations')`**
Runs every `*.sql` file in the directory in file name order. Executed files are recorded in the tracking table, so running again skips them. Use a different tracking table for seeds (for example `alba_seeds`). Each file is sent to the database as one statement batch, so multi-statement support depends on the driver (SQLite, PostgreSQL and MySQL with default PDO settings work).

**`CommandTask(array $command, ?string $name = null)`**
Runs a command from `basePath`. The command is an array (no shell), so there is no shell interpolation, no pipes and no redirects. A non-zero exit code fails the task and shows the output.

```php
new CommandTask(['php', 'artisan', 'migrate', '--force'], 'Run migrations')
```

Under PHP-FPM, `PHP_BINARY` points at the FPM binary, not the CLI. Use `'php'` or a full path to the CLI binary instead.

**`CallbackTask(string $name, Closure $callback)`**
Runs your PHP. The callback receives the `Context` and may return a log line. Throw an exception to fail.

```php
new CallbackTask('Create admin account', function (Context $ctx) {
    $ctx->pdo()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)')->execute([
        $ctx->answer('admin_name'),
        $ctx->answer('admin_email'),
        password_hash($ctx->answer('admin_password'), PASSWORD_DEFAULT),
    ]);

    return 'Admin created';
});
```

### Your own task

Implement `SpykraLabs\Alba\Tasks\Task`:

```php
interface Task
{
    public function name(): string;
    public function run(Context $ctx): string;   // return a log, throw to fail
}
```

Tasks run strictly in order, and each request re-checks that earlier tasks succeeded.

---

## Writing env and config files

Besides the Database and Questions steps, these tasks write files from any step. Paths are relative to `basePath` and must stay inside it.

| Task | Purpose |
|---|---|
| `WriteEnvTask(array $values, ?string $file = null, string $name, bool $keepExisting = false)` | Merge `KEY=value` pairs into an env file (default: the configured env file). With `keepExisting`, keys that already have a value are left alone (good for app keys). |
| `WriteConfigTask(string $path, string $template, ?string $name, bool $overwrite = true)` | Write any text file (PHP, INI, JSON, YAML) from a template. The template can be the text itself or the path of a template file. |
| `WritePhpConfigTask(string $path, array $config, ?string $name)` | Write a PHP file returning an array (`<?php return [...];`). String values can use placeholders. |
| `CopyFileTask(string $from, string $to, bool $onlyIfMissing = true, ?string $name)` | Copy a file, for example `.env.example` to `.env`. |

```php
TaskStep::commands()->add(
    new CopyFileTask('.env.example', '.env'),
    new WriteEnvTask([
        'APP_URL'  => '{{ answers.app_url }}',
        'APP_KEY'  => 'base64:{{ random.base64:32 }}',
        'LICENSE'  => '{{ license.type }}',
    ], keepExisting: true),
    new WritePhpConfigTask('config/local.php', [
        'site'   => ['name' => '{{ answers.app_name }}', 'url' => '{{ answers.app_url }}'],
        'db'     => ['host' => '{{ db.host }}', 'name' => '{{ db.database }}'],
    ]),
    new WriteConfigTask('config/app.ini', "name = {{ answers.app_name }}\nsecret = {{ random.hex:16 }}\n"),
);
```

### Placeholders

| Placeholder | Value |
|---|---|
| `{{ db.driver }}`, `{{ db.host }}`, `{{ db.port }}`, `{{ db.database }}`, `{{ db.username }}`, `{{ db.password }}` | The saved database settings |
| `{{ db.url }}` | A URL such as `mysql://user:pass@host:3306/name` (credentials are URL encoded) |
| `{{ db.path }}` | The absolute path of the SQLite file |
| `{{ answers.NAME }}` | An answer from the `Questions` step |
| `{{ license.type }}`, `{{ license.meta.KEY }}` | The verified licence |
| `{{ path.base }}` | The application base path |
| `{{ app.route }}` | The installer route |
| `{{ random.hex:N }}` | N random bytes as hex (2N characters) |
| `{{ random.base64:N }}` | N random bytes, base64 encoded |
| `{{ random.alnum:N }}` | N random letters and digits |

Filters: `{{ answers.name|php }}` gives a quoted, escaped PHP string, `|json` a JSON string, `|url` URL encodes, `|base64` base64 encodes. Unknown keys become an empty string.

---

## After installation: who can open the installer?

Once the `Finish` step has run, Alba writes `installed.lock` into its storage directory. You decide what visitors get at the installer route from then on with `whenInstalled()`. Choose one behaviour:

```php
use SpykraLabs\Alba\Installed\InstalledBehavior;
```

### 1. Show a page (default)

Nobody can run the installer. Visitors see a themed page.

```php
->whenInstalled(InstalledBehavior::page(
    title: 'Already installed',
    message: 'This application has been installed and the installer is locked.',
    buttonLabel: 'Open the app',
    buttonUrl: null,   // null = the redirectTo URL, '' = no button
    status: 200,       // HTTP status of the page, for example 403
));
```

All arguments are optional. The defaults are shown above.

### 2. Return an HTTP status

Nobody can run the installer. Visitors get only a status code, which hides the installer completely.

```php
->whenInstalled(InstalledBehavior::status(404));            // looks like the route does not exist
->whenInstalled(InstalledBehavior::status(403, 'Forbidden')); // status with a plain text body
->whenInstalled(InstalledBehavior::status(410));            // gone
```

### 3. Redirect

Nobody can run the installer. Visitors are sent elsewhere.

```php
->whenInstalled(InstalledBehavior::redirect('/'));          // 302 to the home page
->whenInstalled(InstalledBehavior::redirect('/login', 301));
```

### 4. Keep the wizard available

Visitors can still use the installer. Choose this only if your app offers re-configuration or re-installation. The lock file is ignored, and the wizard starts again from step one.

```php
->whenInstalled(InstalledBehavior::wizard())
->guard(fn ($request) => my_app_user_is_admin())   // strongly recommended
```

Anyone who can reach the route can change your setup, so always combine this mode with a [guard](#access-control-guard). Finishing the wizard again writes a new lock file.

### Re-running the installer

To run the installer again in the locked modes, delete `installed.lock` from the Alba storage directory (`storage/alba/installed.lock` by default).

---

## Access control (guard)

A guard decides who can reach the installer at all, before installation and after. It runs on every installer request, including the "installed" behaviours, but not for Alba's own assets.

```php
->guard(function (\SpykraLabs\Alba\Http\Request $request): bool {
    // only allow a private key, an office IP, or a signed-in admin
    return ($request->query['key'] ?? null) === getenv('INSTALL_KEY')
        || $request->ip === '203.0.113.10';
})
```

Return `false` to block. Blocked requests get a plain "Access to the installer is not allowed" page with status 403.

The request object exposes `method`, `path`, `query`, `body`, `cookies`, `headers` (lower-cased names), `ip` and `input($key, $default)`.

Note that a `?key=` in the URL only applies to the request that carries it. If you need to keep the key across the wizard, set a cookie or check a header instead.

---

## Branding, headings and instructions

### Logo

```php
->theme([
    'title' => 'Acme Setup',                                   // sidebar name and page title
    'logo'  => '/img/logo.svg',                                // one logo for both modes
])

->theme([
    'logo' => ['light' => '/img/logo-dark-text.svg', 'dark' => '/img/logo-light-text.svg'],
])
```

With a `light`/`dark` pair, Alba shows the logo that matches the active colour mode and switches when the user toggles the theme.

### "Powered by" line

```php
->poweredBy('Powered by Acme Inc.', 'https://acme.example')   // text and optional link
->poweredBy('Built with care by Acme')                        // text only
->poweredBy(false)                                            // hide it
```

The text is escaped. The default line credits Alba.

### Per-step headings and instructions

```php
Database::make()
    ->withTitle('Database')                                       // sidebar
    ->withHeading('Connect your database', 'We need somewhere to store your data.')
    ->withInstructions('
        <p>Create an empty database first, then enter its details.</p>
        <ul><li>Host is usually <code>localhost</code></li></ul>
    ');
```

Instructions are trusted HTML from the developer (links, lists and `<code>` all work). Never pass user input into them.

---

## Theming

Alba's look is controlled by CSS variables, so a theme can be as small as a few colours. The default theme is monochrome with square corners, and supports **dark and light** modes: it follows the visitor's operating system, and a toggle button in the sidebar overrides it (remembered in the browser).

### Quick tweaks

```php
->theme(['brand' => '#4f46e5', 'radius' => '8px', 'font' => 'Inter, sans-serif'])
->extraCss(__DIR__ . '/installer.css')
```

### Design tokens

Variables are set as `--alba-{name}`.

| Token | Purpose |
|---|---|
| `bg` | Page background |
| `fg` | Text |
| `muted` | Sidebar, code blocks, subtle surfaces |
| `muted-fg` | Secondary text |
| `border` | Lines and outlines |
| `brand` | Primary buttons, active step, accents |
| `brand-fg` | Text on brand colour |
| `ok` | Success colour |
| `bad` | Error colour |
| `radius` | Corner radius (default `0px`) |
| `font` | Body font stack |
| `mono` | Monospace font stack |

### Theme packs

A theme pack bundles tokens, CSS, templates and assets. Anyone can build one, for sharing or selling. A pack implements `SpykraLabs\Alba\Themes\Theme`:

| Method | Description |
|---|---|
| `name()` | Display name. |
| `tokens()` | `['shared' => [...], 'light' => [...], 'dark' => [...]]`. Token names without the `--alba-` prefix. |
| `cssFile()` | Absolute path to an extra stylesheet, or `null`. |
| `viewsPath()` | Folder of template overrides, or `null`. |
| `assetsPath()` | Folder of images and fonts, served at `{route}/_alba/theme/{file}`, or `null`. |
| `logo()` | A URL or a `['light' => ..., 'dark' => ...]` pair, or `null`. |

**Option A: a folder** (easy to zip and distribute)

```
my-theme/
  theme.json
  theme.css        optional
  views/           optional template overrides
  assets/          optional images and fonts
```

`theme.json`:

```json
{
  "name": "Ocean",
  "logo": {
    "light": "/install/_alba/theme/logo-light.svg",
    "dark": "/install/_alba/theme/logo-dark.svg"
  },
  "tokens": {
    "shared": { "radius": "10px", "font": "Georgia, serif" },
    "light": { "bg": "#f4f9fc", "fg": "#0b2540", "muted": "#e3eef6", "muted-fg": "#4a6a85", "border": "#c5d9e8", "brand": "#0369a1", "brand-fg": "#ffffff" },
    "dark":  { "bg": "#07141f", "fg": "#e2f1fb", "muted": "#0d2334", "muted-fg": "#84a7c1", "border": "#17384f", "brand": "#38bdf8", "brand-fg": "#04121c" }
  }
}
```

Use it:

```php
use SpykraLabs\Alba\Themes\DirectoryTheme;

->theme(DirectoryTheme::from(__DIR__ . '/themes/ocean'))
```

**Option B: a PHP class**, ideal for a Composer package:

```php
use SpykraLabs\Alba\Themes\BaseTheme;

final class OceanTheme extends BaseTheme
{
    public function name(): string { return 'Ocean'; }

    public function tokens(): array
    {
        return [
            'shared' => ['radius' => '10px'],
            'light'  => ['bg' => '#f4f9fc', 'fg' => '#0b2540', 'brand' => '#0369a1', 'brand-fg' => '#fff'],
            'dark'   => ['bg' => '#07141f', 'fg' => '#e2f1fb', 'brand' => '#38bdf8', 'brand-fg' => '#04121c'],
        ];
    }

    public function cssFile(): ?string   { return __DIR__ . '/theme.css'; }
    public function viewsPath(): ?string { return __DIR__ . '/views'; }
    public function assetsPath(): ?string { return __DIR__ . '/assets'; }
}
```

A ready sample lives in `demo/themes/ocean`.

### Precedence

From lowest to highest: package defaults, then the theme pack, then the `->theme([...])` array (title, logo, brand, radius, font), then `extraCss()`, then template overrides from `viewsPath()`. A buyer of a theme can therefore still tweak the brand colour or logo in their own project.

Token values are validated: names must be lowercase letters, digits and dashes, and values may not contain `;`, `{`, `}`, `<`, `>` or backslashes.

### Overriding templates

Copy any file from `resources/views` into your views folder (with the same name) and change it. Template lookup order: `viewsPath()` folders, then theme pack views, then the package.

Templates: `layout`, `partials_head`, `welcome`, `checks` (requirements and permissions), `database`, `license`, `questions`, `tasks`, `finish`, `installed`, `blocked`.

Templates are plain PHP. These variables are available:

- `$e($value)` escapes a string. Always use it for anything user-supplied.
- `$view->render('partial', get_defined_vars())` includes another template.
- Everywhere: `$alba`, `$ctx`, `$token` (CSRF), `$steps`, `$done`, `$active`.
- On step pages: `$step`, `$errors` (field to message), `$old` (submitted values), `$notice`, plus step specific data (`$checks`, `$fields`, `$tasks`, `$results` and so on).

Any form you write must include `<input type="hidden" name="_token" value="<?= $e($token) ?>">`.

---

## Custom steps

Extend `AbstractStep` (recommended) or implement `StepInterface`:

```php
use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Steps\{AbstractStep, StepResult};
use SpykraLabs\Alba\Support\Context;

final class MailStep extends AbstractStep
{
    protected string $key = 'mail';
    protected string $title = 'Email';
    protected string $description = 'Configure outgoing email.';

    public function viewData(Context $ctx): array
    {
        return ['host' => $ctx->state->get('mail_host', '')];
    }

    public function handle(Request $request, Context $ctx): StepResult
    {
        $host = trim((string) $request->input('host'));

        if ($host === '') {
            return StepResult::fail(['host' => 'The mail host is required.']);
        }

        $ctx->state->put('mail_host', $host);

        return StepResult::ok('Mail settings saved.');
    }
}
```

Then create the template `mail.php` in a views folder registered with `->viewsPath()` (the template name is `view()`, which defaults to the step key):

```php
<?= $view->render('partials_head', get_defined_vars()) ?>
<form method="post">
  <input type="hidden" name="_token" value="<?= $e($token) ?>">
  <label class="alba-field"><span>Mail host</span>
    <input name="host" value="<?= $e($old['host'] ?? $host) ?>">
    <?php if (isset($errors['host'])): ?><small class="alba-err"><?= $e($errors['host']) ?></small><?php endif ?>
  </label>
  <div class="alba-actions"><button class="alba-btn">Continue</button></div>
</form>
```

`StepResult`:

- `StepResult::ok(?string $message)`: mark the step complete. The optional message is shown once on the next page.
- `StepResult::fail(array|string $errors)`: stay on the step. An array maps field names to messages; a string is shown as a general error. Responds with HTTP 422.

`StepInterface` methods: `key()`, `title()`, `description()`, `heading()`, `subheading()`, `instructions()`, `view()`, `viewData(Context)`, `handle(Request, Context)`.

Reusable CSS classes: `alba-field`, `alba-grid`, `alba-actions`, `alba-btn`, `alba-btn is-ghost`, `alba-alert is-ok`, `alba-alert is-bad`, `alba-lead`, `alba-err`.

---

## The Context object

Steps and tasks receive `SpykraLabs\Alba\Support\Context`:

| Member | Description |
|---|---|
| `$ctx->alba` | The configured `Alba` instance. |
| `$ctx->state` | The state store: `get`, `put`, `pull`, `forget`, `markDone`, `isDone`. |
| `$ctx->url($path = '')` | Build a URL under the installer route. |
| `$ctx->path($relative = '')` | Absolute path from a path relative to `basePath`. Absolute paths pass through. |
| `$ctx->answer($name, $default = null)` | An answer from the `Questions` step. |
| `$ctx->license()` | The verified licence (`type`, `meta`, `log`) or `null`. |
| `$ctx->pdo()` | A PDO connection built from the saved database settings. Errors throw exceptions. |

---

## State, lock file and `.env`

- **State file:** `{storagePath}/state.json` holds progress, database details, licence result, answers and task results while installing.
- **Lock file:** `{storagePath}/installed.lock` is created by the Finish step. While it exists the [installed behaviour](#after-installation-who-can-open-the-installer) applies.
- **After finishing:** the state file is deleted. Only the lock file remains.
- **Storage directory:** created automatically (`0775`) with a `.htaccess` that denies web access on Apache. On Nginx, block it yourself or keep it outside the web root with `->storagePath('/var/app-private/alba')`.
- **Env file:** `Database` and `Questions` (with `env:`) merge `KEY=value` pairs into the env file. Existing keys are replaced, other lines are preserved, values that need it are quoted.
- **No sessions:** Alba does not use PHP sessions, so it works before any framework or database exists.

---

## Security

Built in:

- CSRF protection: a random cookie (`alba_csrf`, HttpOnly, SameSite=Lax) must match a token sent with every POST and every task request.
- Locked after installation (see [installed behaviour](#after-installation-who-can-open-the-installer)).
- Optional [guard](#access-control-guard) for allow-lists or keys.
- Output is escaped in all built-in templates.
- File actions are confined to the application path; theme assets are confined to the theme's assets folder (no directory traversal).
- Responses send `Cache-Control: no-store` and `X-Robots-Tag: noindex, nofollow`.
- Commands run as argument arrays, never through a shell.

Your responsibility:

- Serve the installer over HTTPS, since database passwords are submitted through it.
- Keep the storage directory private. Until installation finishes, `state.json` contains the database password and answers (including password fields) in plain text.
- Do not embed secrets (such as an Envato token) in code that buyers receive.
- Use a guard if the installer is reachable by the public before you install.
- Delete or block the installer route once you no longer need it (for example with `InstalledBehavior::status(404)`).

---

## Demo

A runnable plain PHP demo covers every step with SQLite.

```bash
cd alba
composer dump-autoload
php -S localhost:8088 -t demo/public
```

Open `http://localhost:8088/install`. Use the licence key `ALBA-PRO-0001` (pro edition) or `ALBA-LITE-0001` (lite edition). The demo shows requirements, permissions, database, licence with file copy and delete, custom questions, migration, seeding, post-install commands and the finish step.

Useful switches (environment variables read by `demo/alba.php`):

```bash
ALBA_THEME=ocean php -S localhost:8088 -t demo/public              # sample theme pack
ALBA_INSTALLED=status php -S localhost:8088 -t demo/public         # page | status | redirect | wizard
```

Reset the demo to its pre-install state:

```bash
demo/reset.sh
```

---

## Troubleshooting

**"Page expired" (419).** The CSRF cookie is missing or changed. Reload the page. Make sure the browser accepts cookies for the site and the installer is not being cached by a proxy.

**Alba cannot create its storage directory.** Make the parent of `storagePath` writable, or set `->storagePath()` to a writable location.

**Every URL shows "Not found".** The request is not reaching your front controller, or the URL does not start with the configured route. Check your rewrite rules and `route()`.

**Assets look unstyled.** Alba serves `{route}/_alba/alba.css` through PHP. If your server serves static-looking paths directly (for example a `location ~ \.css$` rule), send that path to PHP too.

**Database step fails.** The message comes from PDO. Confirm that the matching `pdo_mysql`, `pdo_pgsql` or `pdo_sqlite` extension is enabled, and that the database already exists (Alba does not create databases).

**A command task fails under FPM.** Use `'php'` instead of `PHP_BINARY` and make sure the web user can run it.

**Installer keeps redirecting to the first step.** Progress is stored in `state.json`. If that file is deleted or the storage path changed, progress restarts.

**I want to run the installer again.** Delete `installed.lock` from the storage directory, or use `InstalledBehavior::wizard()` with a guard.

---

## Project status

Verified: the full flow (all steps, licence based file actions, env writing, migrations, seeding, commands, finish and lock) runs end to end in the demo, all four installed behaviours were exercised, and the `Laravel` preset was run through the wizard against a real Laravel skeleton (`artisan migrate`, `db:seed`, `storage:link`, `optimize:clear`, APP_KEY and `.env` writing).

Partly verified: for Symfony, CodeIgniter, Yii 2, CakePHP, WordPress, Phinx and Doctrine Migrations, the config files Alba writes (`.env.local`, `.env`, `config/db.php`, `config/app_local.php`, `wp-config.php`) were generated and inspected, and the placeholder and config tasks were exercised. The framework commands those presets run (for example `bin/console doctrine:migrations:migrate`) were not executed against real applications, and the Drupal preset was not run at all.

Not yet verified: the Laravel and PSR-15 adapters have not been run against real Laravel, Symfony or Slim applications, the Envato verifier has not been called against the live API, and the automated test suite is still to be written. Treat those parts as untested until you have tried them in your own project, and please report what you find.

---

## Contributing

Issues and pull requests are welcome. Keep the core dependency free and framework agnostic, follow PSR-12, add types everywhere, and include a test for behaviour you change.

---

## License

Alba is open source software released under the [MIT License](LICENSE).
