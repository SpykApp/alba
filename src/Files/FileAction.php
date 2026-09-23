<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Files;

use SpykraLabs\Alba\Support\Context;

interface FileAction
{
    /** @return string a one-line description of what was done */
    public function apply(Context $ctx): string;
}
