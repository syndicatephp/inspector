<?php

namespace Syndicate\Inspector\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class SimpleBulkModelInspectionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * @param class-string<Model> $modelClass The fully qualified class name of the models to check.
     */
    public function __construct(
        public string       $modelClass,
        public ?RemarkLevel $level = null)
    {
    }

    public function uniqueId(): string
    {
        return $this->modelClass . ':simple:' . ($this->level->value ?? 'all');
    }

    public function handle(): void
    {
        try {
            $baseQuery = $this->modelClass::query();

            if ($this->level instanceof RemarkLevel) {
                $baseQuery->whereHas('inspectionReport', function ($query) {
                    $query->where('level', $this->level);
                });
            }

            foreach ($baseQuery->cursor() as $model) {
                InspectionJob::dispatch($model);
            }
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }
}
