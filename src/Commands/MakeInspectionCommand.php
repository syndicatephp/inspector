<?php

namespace Syndicate\Inspector\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class MakeInspectionCommand extends Command
{
    protected $signature = 'make:inspection {model? : The model name}';

    protected $description = 'Create a new Syndicate Inspection class';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelArg = $this->argument('model');
        $modelInput = is_string($modelArg) ? trim($modelArg) : '';

        if (empty($modelInput)) {
            $modelInput = text(
                label: 'What is the name of the model?',
                placeholder: 'E.g. App\\Models\\Post',
                required: true,
                hint: 'Include the FQN if the model is not in the default App\\Models namespace.',
            );
        }

        if (class_exists($modelInput)) {
            $model = $modelInput;
        } elseif (class_exists('App\\Models\\' . $modelInput)) {
            $model = 'App\\Models\\' . $modelInput;
        } else {
            error("Model [{$modelInput}] does not exist.");
            return static::FAILURE;
        }


        $inspectionName = class_basename($model) . 'Inspection';
        $targetDir = app_path('Syndicate/Inspector/Inspections');

        if (!$this->files->isDirectory($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
        }

        $path = $targetDir . '/' . $inspectionName . '.php';

        if ($this->files->exists($path)) {
            error("$inspectionName already exists.");
            return static::FAILURE;
        }

        $stub = $this->buildClass(
            seoName: $inspectionName,
            modelFqn: $model,
            modelName: class_basename($model),
        );

        $this->files->put($path, $stub);

        info("Successfully created $inspectionName");

        return self::SUCCESS;
    }

    protected function buildClass(string $seoName, string $modelFqn, string $modelName): string
    {
        $stub = $this->files->get($this->stubPath());

        return str_replace(
            [
                '{{ inspectionName }}',
                '{{ modelFqn }}',
                '{{ modelName }}',
            ],
            [
                $seoName,
                $modelFqn,
                $modelName,
            ],
            $stub
        );
    }

    protected function stubPath(): string
    {
        $published = base_path('stubs/syndicate/inspector/inspection.stub');

        if (file_exists($published)) {
            return $published;
        }

        return __DIR__ . '/../../stubs/inspection.stub';
    }
}
