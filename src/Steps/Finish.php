<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;

final class Finish extends AbstractStep
{
    protected string $key = 'finish';

    protected string $title = 'Finish';

    protected string $description = 'You are all set.';

    private string $message = 'The application has been installed. For security the installer is now locked.';

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function viewData(Context $ctx): array
    {
        return ['message' => $this->message, 'license' => $ctx->license()];
    }

    /** The kernel locks the installer and clears state after this succeeds. */
    public function handle(Request $request, Context $ctx): StepResult
    {
        return StepResult::ok();
    }
}
