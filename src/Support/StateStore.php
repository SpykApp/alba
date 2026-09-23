<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

use RuntimeException;

/**
 * File-backed installer state. Works before any framework/session exists.
 */
final class StateStore
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function __construct(private string $dir) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->load()[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->load();
        $this->data[$key] = $value;
        $this->save();
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function forget(string $key): void
    {
        $this->load();
        unset($this->data[$key]);
        $this->save();
    }

    public function markDone(string $step): void
    {
        $this->put('done', array_values(array_unique([...$this->get('done', []), $step])));
    }

    public function isDone(string $step): bool
    {
        return in_array($step, $this->get('done', []), true);
    }

    public function clear(): void
    {
        $this->data = [];
        @unlink($this->file());
    }

    public function isInstalled(): bool
    {
        return is_file($this->dir.'/installed.lock');
    }

    public function lock(): void
    {
        $this->ensureDir();
        file_put_contents($this->dir.'/installed.lock', date(DATE_ATOM));
    }

    private function file(): string
    {
        return $this->dir.'/state.json';
    }

    private function load(): array
    {
        if ($this->data === null) {
            $this->data = is_file($this->file()) ? (json_decode((string) file_get_contents($this->file()), true) ?: []) : [];
        }

        return $this->data;
    }

    private function save(): void
    {
        $this->ensureDir();
        file_put_contents($this->file(), json_encode($this->data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function ensureDir(): void
    {
        if (! is_dir($this->dir) && ! @mkdir($this->dir, 0775, true) && ! is_dir($this->dir)) {
            throw new RuntimeException("Alba cannot create its storage directory: {$this->dir}");
        }
        if (! is_file($this->dir.'/.htaccess')) {
            @file_put_contents($this->dir.'/.htaccess', "Require all denied\nDeny from all\n");
        }
    }
}
