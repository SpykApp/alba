<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\EnvWriter;
use SpykraLabs\Alba\Support\Validator;

/** Ask the user for arbitrary information; optionally write answers to the env file. */
final class Questions extends AbstractStep
{
    protected string $key = 'settings';

    protected string|array $title = 'Settings';

    protected string|array $description = 'Tell us about your installation.';

    /** @var array<string, array<string, mixed>> */
    private array $fields = [];

    /**
     * @param  string|array<string, string>  $label  text, or ['en' => ..., 'es' => ...]
     * @param  string|array<string, string>|null  $help  same
     * @param  string  $type  text|email|password|url|number|select|textarea
     * @param  array<string, string>  $options  for select fields (value => label)
     */
    public function field(
        string $name,
        string|array $label,
        string $type = 'text',
        string $rules = '',
        ?string $env = null,
        string $default = '',
        string|array|null $help = null,
        array $options = [],
    ): self {
        $this->fields[$name] = compact('label', 'type', 'rules', 'env', 'default', 'help', 'options');

        return $this;
    }

    public function view(): string
    {
        return 'questions';
    }

    public function viewData(Context $ctx): array
    {
        return ['fields' => $this->fields, 'saved' => $ctx->state->get('answers', [])];
    }

    public function handle(Request $request, Context $ctx): StepResult
    {
        $errors = Validator::validate($this->fields, $request->body);
        if ($errors) {
            return StepResult::fail($errors);
        }

        $answers = [];
        $env = [];
        foreach ($this->fields as $name => $field) {
            $answers[$name] = trim((string) $request->input($name, ''));
            if ($field['env']) {
                $env[$field['env']] = $answers[$name];
            }
        }

        $ctx->state->put('answers', $answers);
        if ($env && $ctx->alba->envFile !== null) {
            (new EnvWriter($ctx->path($ctx->alba->envFile)))->set($env);
        }

        return StepResult::ok();
    }
}
