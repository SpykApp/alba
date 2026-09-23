<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

use SpykraLabs\Alba\Alba;

/**
 * Looks up UI strings. Order (highest first): developer `translations()`,
 * developer lang folder, package language file; then the same for the
 * fallback locale.
 */
final class Translator
{
    /** @var array<string, array<string, string>> */
    private array $cache = [];

    public function __construct(private Alba $alba, public string $locale) {}

    /** @param array<string, scalar> $replace */
    public function get(string $key, array $replace = [], ?string $default = null): string
    {
        foreach ($this->chain() as $locale) {
            $line = $this->load($locale)[$key] ?? null;
            if ($line !== null) {
                return $this->replace($line, $replace);
            }
        }

        return $this->replace($default ?? $key, $replace);
    }

    /** Resolve developer text that may be a plain string or ['en' => ..., 'es' => ...]. */
    public function text(string|array|null $value): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }
        foreach ([...$this->chain(), array_key_first($value)] as $locale) {
            if (isset($value[$locale])) {
                return (string) $value[$locale];
            }
        }

        return '';
    }

    /** @return array<string, string> locale code => native language name */
    public function available(): array
    {
        $codes = [];
        foreach ($this->directories() as $dir) {
            foreach (glob($dir.'/*.php') ?: [] as $file) {
                $codes[basename($file, '.php')] = true;
            }
        }
        foreach (array_keys($this->alba->translations) as $code) {
            $codes[$code] = true;
        }
        if ($this->alba->languages !== null) {
            $codes = array_intersect_key($codes, array_flip($this->alba->languages));
        }

        $out = [];
        foreach (array_keys($codes) as $code) {
            $out[$code] = $this->load($code)['meta.name'] ?? strtoupper($code);
        }
        ksort($out);

        return $out;
    }

    public function direction(): string
    {
        return $this->get('meta.dir', [], 'ltr') === 'rtl' ? 'rtl' : 'ltr';
    }

    /** @return list<string> */
    private function chain(): array
    {
        $chain = [$this->locale];
        if (str_contains($this->locale, '-')) {
            $chain[] = explode('-', $this->locale)[0];
        }
        $chain[] = $this->alba->fallbackLocale;
        $chain[] = 'en';

        return array_values(array_unique($chain));
    }

    /** @return list<string> */
    private function directories(): array
    {
        return array_values(array_filter([
            dirname(__DIR__, 2).'/resources/lang',
            $this->alba->langPath,
        ], fn (?string $d) => $d && is_dir($d)));
    }

    /** @return array<string, string> */
    private function load(string $locale): array
    {
        if (isset($this->cache[$locale])) {
            return $this->cache[$locale];
        }

        $lines = [];
        foreach ($this->directories() as $dir) {
            $file = "$dir/$locale.php";
            if (is_file($file)) {
                $lines = array_merge($lines, (array) (require $file));
            }
        }

        return $this->cache[$locale] = array_merge($lines, $this->alba->translations[$locale] ?? []);
    }

    /** @param array<string, scalar> $replace */
    private function replace(string $line, array $replace): string
    {
        foreach ($replace as $name => $value) {
            $line = str_replace(':'.$name, (string) $value, $line);
        }

        return $line;
    }
}
