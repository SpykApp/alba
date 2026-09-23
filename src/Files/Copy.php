<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Files;

use RuntimeException;
use SpykraLabs\Alba\Support\Context;

final class Copy implements FileAction
{
    private function __construct(private string $from, private string $to) {}

    /** Copy a file or directory (recursively); paths are relative to the app base path. */
    public static function from(string $from, string $to): self
    {
        return new self($from, $to);
    }

    public function apply(Context $ctx): string
    {
        $from = $ctx->path($this->from);
        $to = Guard::inside($ctx, $this->to);
        if (! file_exists($from)) {
            throw new RuntimeException("Source [$this->from] does not exist.");
        }

        self::copy($from, $to);

        return "copied {$this->from} -> {$this->to}";
    }

    private static function copy(string $from, string $to): void
    {
        if (is_file($from)) {
            @mkdir(dirname($to), 0775, true);
            if (! copy($from, $to)) {
                throw new RuntimeException("Could not write $to.");
            }

            return;
        }
        @mkdir($to, 0775, true);
        foreach (array_diff(scandir($from) ?: [], ['.', '..']) as $item) {
            self::copy("$from/$item", "$to/$item");
        }
    }
}
