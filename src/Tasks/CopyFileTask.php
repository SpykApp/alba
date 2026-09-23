<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Tasks;

use RuntimeException;
use SpykraLabs\Alba\Files\Guard;
use SpykraLabs\Alba\Support\Context;

/** Copy a file, e.g. `.env.example` to `.env`, optionally only when the target is missing. */
final class CopyFileTask implements Task
{
    public function __construct(
        private string $from,
        private string $to,
        private bool $onlyIfMissing = true,
        private ?string $name = null,
    ) {}

    public function name(): string
    {
        return $this->name ?? "Copy {$this->from} to {$this->to}";
    }

    public function run(Context $ctx): string
    {
        $to = Guard::inside($ctx, $this->to);
        if ($this->onlyIfMissing && is_file($to)) {
            return "{$this->to} already exists";
        }
        if (! is_file($ctx->path($this->from)) || ! copy($ctx->path($this->from), $to)) {
            throw new RuntimeException("Could not copy {$this->from} to {$this->to}.");
        }

        return "copied {$this->from} to {$this->to}";
    }
}
