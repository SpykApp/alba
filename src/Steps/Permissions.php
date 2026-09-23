<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;

final class Permissions extends AbstractStep
{
    protected string $key = 'permissions';

    protected string $title = 'Permissions';

    protected string $description = 'Check that folders and files are writable.';

    /** @var list<string> */
    private array $paths = [];

    /** Adopt a framework preset's writable paths. */
    public function forFramework(\SpykraLabs\Alba\Frameworks\Framework $framework): self
    {
        return $this->writable($framework->requirements()['writable']);
    }

    /** @param list<string> $paths relative to the app base path (added to any already set) */
    public function writable(array $paths): self
    {
        $this->paths = array_values(array_unique([...$this->paths, ...$paths]));

        return $this;
    }

    public function view(): string
    {
        return 'checks';
    }

    public function viewData(Context $ctx): array
    {
        $checks = [];
        foreach ($this->paths as $relative) {
            $path = $ctx->path($relative);
            // A missing path only needs a writable parent (it will be created).
            $target = file_exists($path) ? $path : dirname($path);
            $ok = is_writable($target);
            $mode = file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : 'will be created';
            $checks[] = ['label' => $relative, 'ok' => $ok, 'detail' => $ok ? "Writable ($mode)" : 'Not writable'];
        }

        return ['checks' => $checks, 'passed' => ! in_array(false, array_column($checks, 'ok'), true)];
    }

    public function handle(Request $request, Context $ctx): StepResult
    {
        return $this->viewData($ctx)['passed']
            ? StepResult::ok()
            : StepResult::fail('Make the listed paths writable, then re-check.');
    }
}
