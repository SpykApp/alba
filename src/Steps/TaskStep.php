<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Lang;
use SpykraLabs\Alba\Tasks\Task;
use Throwable;

/** A step that runs an ordered list of tasks (migrate, seed, post-install commands). */
final class TaskStep extends AbstractStep
{
    /** @var list<Task> */
    private array $tasks = [];

    private string|array $button = 'Run';

    public static function migrate(): self
    {
        return self::preset('migrate', 'Migrate', 'Create the database tables.', 'Run migrations');
    }

    public static function seed(): self
    {
        return self::preset('seed', 'Seed', 'Fill the tables with initial data.', 'Seed database');
    }

    public static function commands(): self
    {
        return self::preset('commands', 'Finalize', 'Run post-install commands.', 'Run commands');
    }

    /**
     * A custom task step. Texts may be strings or locale keyed arrays.
     *
     * @param  string|array<string, string>  $title
     */
    public static function named(string $key, string|array $title, string|array $description = '', string|array $button = 'Run'): self
    {
        $step = self::preset($key, $title, $description, $button);
        $step->titleSet = $step->descriptionSet = true;
        $step->button = $button;

        return $step;
    }

    /** Built-in step: its texts come from the language files. */
    private static function preset(string $key, string|array $title, string|array $description, string|array $button): self
    {
        $step = new self;
        $step->key = $key;
        $step->title = $title;
        $step->description = $description;
        $step->button = $button;

        return $step;
    }

    /**
     * Fill this step from a framework preset: migrate() gets the framework's
     * migrations, seed() its seeders, commands() its post-install tasks.
     */
    public function using(\SpykraLabs\Alba\Frameworks\Framework $framework): self
    {
        $tasks = match ($this->key) {
            'migrate' => [$framework->migrate()],
            'seed' => array_filter([$framework->seed()]),
            'commands' => $framework->finalize(),
            default => throw new \LogicException('using() only works on the migrate, seed and commands steps.'),
        };

        return $this->add(...$tasks);
    }

    public function add(Task ...$tasks): self
    {
        array_push($this->tasks, ...$tasks);

        return $this;
    }

    /** @return list<Task> */
    public function tasks(): array
    {
        return $this->tasks;
    }

    public function view(): string
    {
        return 'tasks';
    }

    public function viewData(Context $ctx): array
    {
        $results = $ctx->state->get('tasks', [])[$this->key] ?? [];

        $button = $this->titleSet
            ? Lang::text($this->button)
            : Lang::t("step.{$this->key}.button", [], Lang::text($this->button));

        return ['tasks' => $this->tasks, 'results' => $results, 'button' => $button];
    }

    /** Runs one task by index and records the outcome. @return array{ok: bool, log: string} */
    public function runTask(int $index, Context $ctx): array
    {
        $task = $this->tasks[$index] ?? throw new \OutOfRangeException('Unknown task.');
        $all = $ctx->state->get('tasks', []);

        try {
            $result = ['ok' => true, 'log' => $task->run($ctx)];
        } catch (Throwable $e) {
            $result = ['ok' => false, 'log' => $e->getMessage()];
        }

        $all[$this->key][$index] = $result;
        $ctx->state->put('tasks', $all);

        return $result;
    }

    /** No-JS fallback: run every task that has not succeeded yet, in order. */
    public function handle(Request $request, Context $ctx): StepResult
    {
        foreach ($this->tasks as $i => $task) {
            $done = $ctx->state->get('tasks', [])[$this->key][$i]['ok'] ?? false;
            if (! $done && ! $this->runTask($i, $ctx)['ok']) {
                return StepResult::fail(Lang::t('ui.task_failed', ['name' => $task->name()]));
            }
        }

        return StepResult::ok();
    }
}
