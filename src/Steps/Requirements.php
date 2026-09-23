<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Lang;

final class Requirements extends AbstractStep
{
    protected string $key = 'requirements';

    protected string|array $title = 'Requirements';

    protected string|array $description = 'Check that the server can run the application.';

    private string $php = '8.1.0';

    /** @var list<string> */
    private array $extensions = [];

    /** @var array<string, string> */
    private array $ini = [];

    /** @var list<string> */
    private array $functions = [];

    /** Adopt a framework preset's PHP version and extensions (call before adding your own). */
    public function forFramework(\SpykraLabs\Alba\Frameworks\Framework $framework): self
    {
        $needs = $framework->requirements();
        $this->php = $needs['php'];
        $this->extensions = array_values(array_unique([...$this->extensions, ...$needs['extensions']]));

        return $this;
    }

    public function php(string $minimum): self
    {
        $this->php = $minimum;

        return $this;
    }

    /** @param list<string> $extensions */
    public function extensions(array $extensions): self
    {
        $this->extensions = array_values(array_unique([...$this->extensions, ...$extensions]));

        return $this;
    }

    /** Require a byte-size ini value (e.g. memory_limit) of at least $minimum. */
    public function iniAtLeast(string $key, string $minimum): self
    {
        $this->ini[$key] = $minimum;

        return $this;
    }

    /** @param list<string> $functions functions that must exist / not be disabled */
    public function functions(array $functions): self
    {
        $this->functions = $functions;

        return $this;
    }

    /** @return list<array{label: string, ok: bool, detail: string}> */
    public function checks(): array
    {
        $checks = [[
            'label' => Lang::t('req.php', ['version' => $this->php]),
            'ok' => version_compare(PHP_VERSION, $this->php, '>='),
            'detail' => Lang::t('req.installed', ['version' => PHP_VERSION]),
        ]];

        foreach ($this->extensions as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = ['label' => Lang::t('req.extension', ['name' => $extension]), 'ok' => $loaded, 'detail' => Lang::t($loaded ? 'req.loaded' : 'req.missing')];
        }

        foreach ($this->ini as $key => $minimum) {
            $current = (string) ini_get($key);
            $ok = $current === '-1' || $this->bytes($current) >= $this->bytes($minimum);
            $checks[] = ['label' => Lang::t('req.ini', ['key' => $key, 'min' => $minimum]), 'ok' => $ok, 'detail' => Lang::t('req.current', ['value' => $current === '' ? Lang::t('req.not_set') : $current])];
        }

        foreach ($this->functions as $function) {
            $ok = function_exists($function);
            $checks[] = ['label' => Lang::t('req.function', ['name' => $function]), 'ok' => $ok, 'detail' => Lang::t($ok ? 'req.available' : 'req.function_missing')];
        }

        return $checks;
    }

    public function view(): string
    {
        return 'checks';
    }

    public function viewData(Context $ctx): array
    {
        $checks = $this->checks();

        return ['checks' => $checks, 'passed' => ! in_array(false, array_column($checks, 'ok'), true)];
    }

    public function handle(Request $request, Context $ctx): StepResult
    {
        return $this->viewData($ctx)['passed']
            ? StepResult::ok()
            : StepResult::fail(Lang::t('req.failed'));
    }

    private function bytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
