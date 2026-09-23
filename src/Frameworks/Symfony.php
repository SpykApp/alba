<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database;
use SpykraLabs\Alba\Tasks\{Task, WriteEnvTask};

/** Symfony with Doctrine. Works through `php bin/console`; config goes to `.env.local`. */
final class Symfony extends Framework
{
    public function name(): string
    {
        return 'symfony';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/bin/console') && $this->hasComposerPackage($basePath, 'symfony/framework-bundle');
    }

    /** Writes DATABASE_URL to .env.local (Symfony's convention for machine-specific values). */
    public function writeDatabase(Context $ctx, array $db): void
    {
        $url = Database::url($db, $ctx).($db['driver'] === 'mysql' ? '?charset=utf8mb4' : '');
        $this->writeEnv($ctx, ['DATABASE_URL' => $url], '.env.local');
    }

    public function createDatabase(): Task
    {
        return $this->console(['doctrine:database:create', '--if-not-exists'], 'Create database if missing');
    }

    public function migrate(): Task
    {
        return $this->console(['doctrine:migrations:migrate', '--allow-no-migration'], 'Run migrations');
    }

    /** For projects without migrations: `doctrine:schema:update --force`. */
    public function updateSchema(): Task
    {
        return $this->console(['doctrine:schema:update', '--force'], 'Update database schema');
    }

    /** Needs doctrine/doctrine-fixtures-bundle. */
    public function seed(): Task
    {
        return $this->console(['doctrine:fixtures:load', '--append'], 'Load fixtures');
    }

    public function generateSecret(): Task
    {
        return new WriteEnvTask(
            ['APP_SECRET' => '{{ random.hex:16 }}', 'APP_ENV' => 'prod'],
            '.env.local',
            'Generate APP_SECRET and set production mode',
            keepExisting: true,
        );
    }

    public function clearCache(): Task
    {
        return $this->console(['cache:clear'], 'Clear cache');
    }

    public function installAssets(): Task
    {
        return $this->console(['assets:install', 'public'], 'Install assets');
    }

    /** Any console command. @param list<string> $args */
    public function console(array $args, ?string $name = null): Task
    {
        return $this->cli('bin/console', [...$args, '--no-interaction'], $name ?? 'bin/console '.implode(' ', $args));
    }

    public function finalize(): array
    {
        return [$this->generateSecret(), $this->clearCache(), $this->installAssets()];
    }

    public function requirements(): array
    {
        return [
            'php' => '8.2.0',
            'extensions' => ['ctype', 'iconv', 'intl', 'mbstring', 'pdo', 'xml'],
            'writable' => ['var', '.env.local'],
        ];
    }
}
