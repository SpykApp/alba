<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

use PDO;
use RuntimeException;
use SpykraLabs\Alba\Alba;

/** What every step and task receives. */
final class Context
{
    public function __construct(public Alba $alba, public StateStore $state) {}

    public function url(string $path = ''): string
    {
        return rtrim($this->alba->route, '/').'/'.ltrim($path, '/');
    }

    /** Resolve a path relative to the app's base path. */
    public function path(string $relative = ''): string
    {
        $isAbsolute = str_starts_with($relative, '/') || preg_match('~^[A-Za-z]:[\\\\/]~', $relative);

        return $isAbsolute ? $relative : rtrim($this->alba->basePath.'/'.$relative, '/');
    }

    /** Answers collected by the Questions step. */
    public function answer(string $name, mixed $default = null): mixed
    {
        return $this->state->get('answers', [])[$name] ?? $default;
    }

    /** The licence result stored by the License step, if any. */
    public function license(): ?array
    {
        return $this->state->get('license');
    }

    public function pdo(): PDO
    {
        $db = $this->state->get('db') ?? throw new RuntimeException('Database has not been configured yet.');

        return Database::connect($db, $this);
    }
}
