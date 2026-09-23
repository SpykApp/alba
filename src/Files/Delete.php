<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Files;

use SpykraLabs\Alba\Support\Context;

final class Delete implements FileAction
{
    private function __construct(private string $path) {}

    /** Delete a file or directory (recursively); must live inside the app base path. */
    public static function path(string $path): self
    {
        return new self($path);
    }

    public function apply(Context $ctx): string
    {
        $target = Guard::inside($ctx, $this->path);
        if (! file_exists($target)) {
            return "nothing to delete at {$this->path}";
        }
        self::remove($target);

        return "deleted {$this->path}";
    }

    private static function remove(string $path): void
    {
        if (is_dir($path) && ! is_link($path)) {
            foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
                self::remove("$path/$item");
            }
            rmdir($path);

            return;
        }
        unlink($path);
    }
}
