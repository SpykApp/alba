<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\License;

use Closure;
use SpykraLabs\Alba\Support\Lang;

/** Custom licensing: hand Alba a closure returning a LicenseResult (or bool). */
final class CallbackVerifier implements LicenseVerifier
{
    /** @param Closure(string, array): (LicenseResult|bool) $callback */
    public function __construct(private Closure $callback) {}

    public function verify(string $code, array $extra = []): LicenseResult
    {
        $result = ($this->callback)($code, $extra);

        return $result instanceof LicenseResult
            ? $result
            : ($result ? LicenseResult::valid() : LicenseResult::invalid(Lang::t('license.invalid')));
    }
}
