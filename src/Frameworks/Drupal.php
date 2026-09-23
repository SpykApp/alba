<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database;
use SpykraLabs\Alba\Tasks\{CallbackTask, CommandTask, Task};

/**
 * Drupal 10/11 through Drush. Drush writes settings.php itself during
 * `site:install`, so the database step only stores the connection for later.
 */
final class Drupal extends Framework
{
    public function __construct(private string $drush = 'vendor/bin/drush', private string $profile = 'standard') {}

    public function name(): string
    {
        return 'drupal';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/vendor/bin/drush') && $this->hasComposerPackage($basePath, 'drupal/core-recommended');
    }

    /** Nothing to write: the connection is passed to Drush as --db-url. */
    public function writeDatabase(Context $ctx, array $db): void {}

    /**
     * `drush site:install`. Answer names default to site_name, admin_name,
     * admin_password and admin_email. The password is passed as a command
     * argument, so it is visible in the process list while the command runs.
     *
     * @param  array<string, string>  $answers
     */
    public function migrate(array $answers = []): Task
    {
        $map = $answers + ['site_name' => 'site_name', 'admin_name' => 'admin_name', 'admin_password' => 'admin_password', 'admin_email' => 'admin_email'];

        return new CallbackTask('Install Drupal', function (Context $ctx) use ($map) {
            $db = $ctx->state->get('db', []);
            $cmd = [$this->php, $this->drush, 'site:install', $this->profile,
                '--db-url='.Database::url($db, $ctx),
                '--site-name='.$ctx->answer($map['site_name'], 'Drupal'),
                '--account-name='.$ctx->answer($map['admin_name'], 'admin'),
                '--account-pass='.$ctx->answer($map['admin_password'], ''),
                '--account-mail='.$ctx->answer($map['admin_email'], ''),
                '--yes'];

            return (new CommandTask($cmd))->run($ctx);
        });
    }

    public function configImport(): Task
    {
        return $this->drush(['config:import'], 'Import configuration');
    }

    public function updateDatabase(): Task
    {
        return $this->drush(['updatedb'], 'Run database updates');
    }

    public function rebuildCache(): Task
    {
        return $this->drush(['cache:rebuild'], 'Rebuild caches');
    }

    /** @param list<string> $args */
    public function drush(array $args, ?string $name = null): Task
    {
        return $this->cli($this->drush, [...$args, '--yes'], $name ?? 'drush '.implode(' ', $args));
    }

    public function finalize(): array
    {
        return [$this->rebuildCache()];
    }

    public function requirements(): array
    {
        return [
            'php' => '8.3.0',
            'extensions' => ['gd', 'pdo', 'xml', 'mbstring', 'json'],
            'writable' => ['web/sites/default'],
        ];
    }
}
