<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;

abstract class AbstractStep implements StepInterface
{
    protected string $key = '';

    protected string $title = '';

    protected string $description = '';

    protected ?string $heading = null;

    protected ?string $subheading = null;

    protected ?string $instructions = null;

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
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function heading(): string
    {
        return $this->heading ?? $this->title();
    }

    public function subheading(): string
    {
        return $this->subheading ?? $this->description();
    }

    public function instructions(): ?string
    {
        return $this->instructions;
    }

    /** Override the page heading (the sidebar keeps the short title). */
    public function withHeading(string $heading, ?string $subheading = null): static
    {
        $this->heading = $heading;
        $this->subheading = $subheading ?? $this->subheading;

        return $this;
    }

    /** Guidance for the user on this step. Trusted HTML: links, lists, <code> are fine. */
    public function withInstructions(string $html): static
    {
        $this->instructions = $html;

        return $this;
    }

    public function withTitle(string $title, ?string $description = null): static
    {
        $this->title = $title;
        $this->description = $description ?? $this->description;

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
