<?php

namespace Syndicate\Inspector\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Syndicate\Inspector\Events\BulkInspectionCompleted;
use Throwable;

class BulkModelInspectionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param string $modelClass The fully qualified class name of the models to check.
     */
    public function __construct(public string $modelClass, public ?User $user = null)
    {
        $this->onQueue(Config::get('syndicate.inspector.queue_name') ?? 'default');
    }

    /**
     * Get the unique ID for the job.
     * Prevents duplicate jobs for the exact same class.
     */
    public function uniqueId(): string
    {
        return $this->modelClass;
    }

    public function handle(): void
    {
        try {
            // Step 1: Clean up inspection data for models that should no longer be inspected
            $this->cleanupStaleInspectionData();

            $baseQuery = $this->modelClass::query();

            // Collect all jobs to be batched, filtering out models that shouldn't be inspected
            $jobs = [];
            foreach ($baseQuery->cursor() as $model) {
                if ($model->shouldBeInspected()) {
                    $jobs[] = new InspectionJob($model, $this->user);
                }
            }

            // If no models to process, dispatch event immediately
            if (empty($jobs)) {
                return;
            }

            $modelClass = $this->modelClass;
            $startTime = microtime(true);
            $totalJobs = count($jobs);

            // Create batch with all jobs and dispatch completion event when all finish
            Bus::batch($jobs)
                ->name('Bulk Inspection: ' . class_basename($this->modelClass))
                ->onQueue(Config::get('syndicate.inspector.queue_name') ?? 'default')
                ->finally(function (Batch $batch) use ($modelClass, $startTime, $totalJobs) {
                    $endTime = microtime(true);
                    $totalTimeSeconds = $endTime - $startTime;
                    $averageTimePerInspection = $totalJobs > 0 ? $totalTimeSeconds / $totalJobs : 0.0;

                    Log::info('Bulk inspection batch completed', [
                        'modelClass' => $modelClass,
                        'totalJobs' => $totalJobs,
                        'totalTimeSeconds' => $totalTimeSeconds,
                        'averageTimePerInspection' => $averageTimePerInspection
                    ]);

                    $modelInspectionReport = $modelClass::modelInspectionReport();
                    Event::dispatch(new BulkInspectionCompleted(
                        $modelInspectionReport,
                        $totalTimeSeconds,
                        $averageTimePerInspection
                    ));
                })
                ->dispatch();
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    /**
     * Clean up inspection data for models that should no longer be inspected.
     * This ensures the database stays clean when models change status (e.g., become drafts).
     */
    private function cleanupStaleInspectionData(): void
    {
        $deletedRemarks = 0;
        $deletedReports = 0;

        // Get all models of this class that should not be inspected
        $modelsToCleanup = $this->modelClass::all()->filter(function ($model) {
            return !$model->shouldBeInspected();
        });

        foreach ($modelsToCleanup as $model) {
            // Delete remarks for this model
            $remarksDeleted = $model->inspectionRemarks()->delete();
            $deletedRemarks += $remarksDeleted;

            // Delete report for this model
            if ($model->inspectionReport) {
                $model->inspectionReport()->delete();
                $deletedReports++;
            }
        }

        if ($deletedRemarks > 0 || $deletedReports > 0) {
            Log::info('Cleaned up stale inspection data before bulk inspection', [
                'modelClass' => $this->modelClass,
                'modelsChecked' => $modelsToCleanup->count(),
                'remarksDeleted' => $deletedRemarks,
                'reportsDeleted' => $deletedReports
            ]);
        }
    }
}
