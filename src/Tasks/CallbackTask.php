<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Tasks;

use Closure;
use SpykraLabs\Alba\Support\Context;

final class CallbackTask implements Task
{
    /** @param Closure(Context): (string|null) $callback */
    public function __construct(private string $name, private Closure $callback) {}

    public function name(): string
    {
        return $this->name;
    }

    public function run(Context $ctx): string
    {
        return (string) ($this->callback)($ctx);
    }
}
