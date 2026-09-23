<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

/** Merges KEY=value pairs into an env file, preserving everything else. */
final class EnvWriter
{
    public function __construct(private string $file) {}

    /** @param array<string, scalar|null> $values */
    public function set(array $values): void
    {
        $contents = is_file($this->file) ? (string) file_get_contents($this->file) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->format((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $contents = preg_match($pattern, $contents)
                ? preg_replace($pattern, addcslashes($line, '\\$'), $contents)
                : rtrim($contents, "\n").($contents === '' ? '' : "\n").$line."\n";
        }

        file_put_contents($this->file, $contents, LOCK_EX);
    }

    /** Current value of a key, or null when absent. */
    public function get(string $key): ?string
    {
        if (! is_file($this->file) || ! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', (string) file_get_contents($this->file), $m)) {
            return null;
        }

        return trim($m[1], " \t\"'");
    }

    private function format(string $value): string
    {
        return $value === '' || preg_match('/^[A-Za-z0-9_.\/:@\\-]+$/', $value)
            ? $value
            : '"'.addcslashes($value, "\"\\\n").'"';
    }
}
