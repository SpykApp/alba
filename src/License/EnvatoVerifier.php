<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\License;

use RuntimeException;
use SpykraLabs\Alba\Support\Lang;

/**
 * Verifies an Envato Market purchase code against the author sale API.
 * Needs a personal token with the "View a sale" permission. Keep the token
 * server-side (e.g. behind a licence proxy you control) when distributing a
 * package, since anything shipped in source can be read by buyers.
 */
final class EnvatoVerifier implements LicenseVerifier
{
    /**
     * @param  array<int, string>  $licenseTypes  map of Envato licence names to your own types
     */
    public function __construct(
        private string $token,
        private ?int $itemId = null,
        private array $licenseTypes = ['Regular License' => 'regular', 'Extended License' => 'extended'],
        private string $endpoint = 'https://api.envato.com/v3/market/author/sale',
    ) {}

    public function verify(string $code, array $extra = []): LicenseResult
    {
        if (! preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/i', $code)) {
            return LicenseResult::invalid(Lang::t('license.envato_format'));
        }

        $ch = curl_init($this->endpoint.'?code='.urlencode($code));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$this->token, 'User-Agent: Alba installer'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Could not reach the Envato API.');
        }
        if ($status === 404) {
            return LicenseResult::invalid(Lang::t('license.envato_not_found'));
        }
        if ($status !== 200) {
            throw new RuntimeException("Envato API returned HTTP $status.");
        }

        $sale = json_decode($body, true);
        if ($this->itemId !== null && (int) ($sale['item']['id'] ?? 0) !== $this->itemId) {
            return LicenseResult::invalid(Lang::t('license.envato_other_item'));
        }

        $license = (string) ($sale['license'] ?? '');

        return LicenseResult::valid($this->licenseTypes[$license] ?? 'regular', [
            'buyer' => $sale['buyer'] ?? null,
            'sold_at' => $sale['sold_at'] ?? null,
            'supported_until' => $sale['supported_until'] ?? null,
        ]);
    }
}
