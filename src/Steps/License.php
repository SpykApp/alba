<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use Closure;
use SpykraLabs\Alba\Files\FileAction;
use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\License\LicenseVerifier;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Lang;
use SpykraLabs\Alba\Support\Validator;
use Throwable;

/** Verifies a licence (Envato or custom) and applies file actions for its type. */
final class License extends AbstractStep
{
    protected string $key = 'license';

    protected string|array $title = 'License';

    protected string|array $description = 'Verify your purchase.';

    private ?LicenseVerifier $verifier = null;

    private string|array|null $codeLabel = null;

    /** @var array<string, list<FileAction>> */
    private array $actions = [];

    /** @var list<string> */
    private array $extraFields = [];

    public function verifier(LicenseVerifier|Closure $verifier): self
    {
        $this->verifier = $verifier instanceof Closure ? new \SpykraLabs\Alba\License\CallbackVerifier($verifier) : $verifier;

        return $this;
    }

    /** @param string|array<string, string> $label */
    public function codeLabel(string|array $label): self
    {
        $this->codeLabel = $label;

        return $this;
    }

    /** Extra inputs passed to the verifier, e.g. an email or username. */
    public function extraField(string $name): self
    {
        $this->extraFields[] = $name;

        return $this;
    }

    /**
     * File actions to run when the licence is of $type ("*" = every licence).
     *
     * @param  list<FileAction>  $actions
     */
    public function onType(string $type, array $actions): self
    {
        $this->actions[$type] = $actions;

        return $this;
    }

    private function label(): string
    {
        return $this->codeLabel !== null ? Lang::text($this->codeLabel) : Lang::t('license.code');
    }

    public function viewData(Context $ctx): array
    {
        return ['codeLabel' => $this->label(), 'extraFields' => $this->extraFields, 'license' => $ctx->license()];
    }

    public function handle(Request $request, Context $ctx): StepResult
    {
        $errors = Validator::validate(['code' => ['label' => $this->label(), 'rules' => 'required']], $request->body);
        if ($errors) {
            return StepResult::fail($errors);
        }

        $verifier = $this->verifier ?? throw new \LogicException('License step has no verifier configured.');
        $extra = array_intersect_key($request->body, array_flip($this->extraFields));

        try {
            $result = $verifier->verify(trim((string) $request->input('code')), $extra);
        } catch (Throwable $e) {
            return StepResult::fail(['code' => Lang::t('license.failed', ['message' => $e->getMessage()])]);
        }

        if (! $result->valid) {
            return StepResult::fail(['code' => $result->message ?: Lang::t('license.invalid')]);
        }

        $log = [];
        foreach ([...($this->actions['*'] ?? []), ...($this->actions[$result->type] ?? [])] as $action) {
            try {
                $log[] = $action->apply($ctx);
            } catch (Throwable $e) {
                return StepResult::fail(['code' => Lang::t('license.files_failed', ['message' => $e->getMessage()])]);
            }
        }

        $ctx->state->put('license', ['type' => $result->type, 'meta' => $result->meta, 'log' => $log]);

        return StepResult::ok(Lang::t('license.ok', ['type' => $result->type]));
    }
}
