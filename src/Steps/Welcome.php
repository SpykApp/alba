<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Support\Context;

final class Welcome extends AbstractStep
{
    protected string $key = 'welcome';

    protected string $title = 'Welcome';

    protected string $description = 'Get ready to install.';

    private string $intro = 'This wizard will check your server, connect your database and set the application up. It takes a couple of minutes.';

    public function intro(string $text): self
    {
        $this->intro = $text;

        return $this;
    }

    public function viewData(Context $ctx): array
    {
        return ['intro' => $this->intro];
    }
}
