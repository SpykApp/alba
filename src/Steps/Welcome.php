<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Lang;

final class Welcome extends AbstractStep
{
    protected string $key = 'welcome';

    protected string|array $title = 'Welcome';

    protected string|array $description = 'Get ready to install.';

    private string|array|null $intro = null;

    /** @param string|array<string, string> $text */
    public function intro(string|array $text): self
    {
        $this->intro = $text;

        return $this;
    }

    public function viewData(Context $ctx): array
    {
        return ['intro' => $this->intro !== null ? Lang::text($this->intro) : Lang::t('step.welcome.intro')];
    }
}
