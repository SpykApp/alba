<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Adapters;

use SpykraLabs\Alba\Alba;

/** For plain PHP / any framework: `PlainPhp::serve($alba)` from a front controller. */
final class PlainPhp
{
    public static function serve(Alba $alba): void
    {
        $alba->run();
    }
}
