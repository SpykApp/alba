<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Lang;

abstract class AbstractStep implements StepInterface
{
    protected string $key = '';

    protected string|array $title = '';

    protected string|array $description = '';

    protected bool $titleSet = false;

    protected bool $descriptionSet = false;

    protected string|array|null $heading = null;

    protected string|array|null $subheading = null;

    protected string|array|null $instructions = null;

    public static function make(): static
    {
        return new static;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function title(): string
    {
        return $this->titleSet ? Lang::text($this->title) : Lang::t("step.{$this->key}.title", [], Lang::text($this->title));
    }

    public function description(): string
    {
        return $this->descriptionSet
            ? Lang::text($this->description)
            : Lang::t("step.{$this->key}.description", [], Lang::text($this->description));
    }

    public function heading(): string
    {
        return $this->heading !== null ? Lang::text($this->heading) : $this->title();
    }

    public function subheading(): string
    {
        return $this->subheading !== null ? Lang::text($this->subheading) : $this->description();
    }

    public function instructions(): ?string
    {
        return $this->instructions === null ? null : Lang::text($this->instructions);
    }

    /** Override the page heading (the sidebar keeps the short title). */
    /** @param string|array<string, string> $heading  text, or ['en' => ..., 'es' => ...] */
    public function withHeading(string|array $heading, string|array|null $subheading = null): static
    {
        $this->heading = $heading;
        $this->subheading = $subheading ?? $this->subheading;

        return $this;
    }

    /** Guidance for the user on this step. Trusted HTML: links, lists, <code> are fine. */
    public function withInstructions(string|array $html): static
    {
        $this->instructions = $html;

        return $this;
    }

    /** @param string|array<string, string> $title  text, or ['en' => ..., 'es' => ...] */
    public function withTitle(string|array $title, string|array|null $description = null): static
    {
        $this->title = $title;
        $this->titleSet = true;
        if ($description !== null) {
            $this->description = $description;
            $this->descriptionSet = true;
        }

        return $this;
    }

    public function withKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function view(): string
    {
        return $this->key;
    }

    public function viewData(Context $ctx): array
    {
        return [];
    }

    public function handle(Request $request, Context $ctx): StepResult
    {
        return StepResult::ok();
    }
}
