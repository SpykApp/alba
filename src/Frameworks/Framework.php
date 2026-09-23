<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Files\Guard;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\EnvWriter;
use SpykraLabs\Alba\Tasks\CommandTask;
use SpykraLabs\Alba\Tasks\Task;

/**
 * A framework preset: how a given framework wants its database configured,
 * what it needs from the server, and which commands migrate, seed and finish
 * an install. Everything is driven through the framework's own CLI, so Alba
 * never has to boot the framework itself.
 */
abstract class Framework
{
    /** PHP binary used for CLI commands. Under FPM set a real path here. */
    protected string $php = 'php';

    public static function make(): static
    {
        return new static;
    }

    public function withPhp(string $binary): static
    {
        $this->php = $binary;

        return $this;
    }

    /** Short machine name, e.g. "laravel". */
    abstract public function name(): string;

    /** Does the application at $basePath look like this framework? */
    abstract public function detect(string $basePath): bool;

    /**
     * Persist the database settings the way this framework expects
     * (env keys, a config file, wp-config.php...).
     *
     * @param  array<string, string>  $db  driver, host, port, database, username, password
     */
    abstract public function writeDatabase(Context $ctx, array $db): void;

    abstract public function migrate(): Task;

    /** Null when the framework has no seeding concept. */
    public function seed(): ?Task
    {
        return null;
    }

    /**
     * Post-install tasks: keys, caches, links.
     *
     * @return list<Task>
     */
    public function finalize(): array
    {
        return [];
    }

    /** @return array{php: string, extensions: list<string>, writable: list<string>} */
    abstract public function requirements(): array;

    /** @param list<string> $args */
    protected function cli(string $binary, array $args, string $name): CommandTask
    {
        return new CommandTask([$this->php, $binary, ...$args], $name);
    }

    /** @param array<string, string> $values */
    protected function writeEnv(Context $ctx, array $values, ?string $file = null): void
    {
        $relative = $file ?? $ctx->alba->envFile ?? '.env';
        (new EnvWriter(Guard::inside($ctx, $relative)))->set($values);
    }

    protected function hasComposerPackage(string $basePath, string $package): bool
    {
        $file = $basePath.'/composer.json';

        return is_file($file) && str_contains((string) file_get_contents($file), '"'.$package.'"');
    }
}
