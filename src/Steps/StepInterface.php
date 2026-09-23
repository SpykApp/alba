<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;

interface StepInterface
{
    /** URL segment and unique identifier, e.g. "database". */
    public function key(): string;

    public function title(): string;

    public function description(): string;

    /** Page heading (defaults to the title). */
    public function heading(): string;

    /** Text under the heading (defaults to the description). */
    public function subheading(): string;

    /** Trusted HTML shown in an instructions box, or null. */
    public function instructions(): ?string;

    /** Template name (searched in custom view paths first). */
    public function view(): string;

    /** @return array<string, mixed> extra data for the template */
    public function viewData(Context $ctx): array;

    public function handle(Request $request, Context $ctx): StepResult;
}
