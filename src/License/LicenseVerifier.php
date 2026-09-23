<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\License;

interface LicenseVerifier
{
    /**
     * @param  array<string, mixed>  $extra  additional inputs from the form
     * @throws \Throwable when verification cannot be performed (network error...)
     */
    public function verify(string $code, array $extra = []): LicenseResult;
}
