<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Tasks;

use SpykraLabs\Alba\Support\Context;

interface Task
{
    public function name(): string;

    /** Perform the work. Return a short log; throw to signal failure. */
    public function run(Context $ctx): string;
}
