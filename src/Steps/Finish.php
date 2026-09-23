<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Lang;

final class Finish extends AbstractStep
{
    protected string $key = 'finish';

    protected string|array $title = 'Finish';

    protected string|array $description = 'You are all set.';

    private string|array|null $message = null;

    /** @param string|array<string, string> $message */
    public function message(string|array $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function viewData(Context $ctx): array
    {
        return ['message' => $this->message !== null ? Lang::text($this->message) : Lang::t('step.finish.message'), 'license' => $ctx->license()];
    }

    /** The kernel locks the installer and clears state after this succeeds. */
    public function handle(Request $request, Context $ctx): StepResult
    {
        return StepResult::ok();
    }
}
