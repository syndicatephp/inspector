<?php

namespace Syndicate\Inspector\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Syndicate\Inspector\Checks\HttpRequestError;
use Syndicate\Inspector\Contracts\Check;
use Syndicate\Inspector\Contracts\Inspection;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\DTOs\Finding;
use Syndicate\Inspector\DTOs\InspectionContext;
use Syndicate\Inspector\DTOs\InspectionReport;
use Syndicate\Inspector\DTOs\LevelCounts;
use Syndicate\Inspector\Enums\RemarkLevel;
use Syndicate\Inspector\Events\InspectionCompleted;
use Syndicate\Inspector\Models\Remark;
use Syndicate\Inspector\Models\Report;
use Throwable;

class InspectorService
{
    public function inspect(Model $model): void
    {
        $this->runAndReport($this->inspectionFor($model));
    }

    public function runAndReport(Inspection $inspection): void
    {
        $report = $this->run($inspection);
        $this->record($report);
    }

    public function run(Inspection $inspection): InspectionReport
    {
        $url = $inspection->url();
        $httpOptions = $inspection->httpOptions();
        $checks = $inspection->checks();
        $findings = new Collection();

        // 1. Make the HTTP request to get the Response object.
        try {
            $response = Http::withOptions($httpOptions)->get($url);
        } catch (Throwable $e) {
            $httpFinding = Finding::fatal('HTTP request failed: ' . $e->getMessage(), HttpRequestError::class, $url);
            return new InspectionReport($inspection, collect([$httpFinding]));
        }

        // 2. Create the context for this inspection run.
        $context = InspectionContext::make($inspection, $response);

        // 3. Execute each check against the context.
        foreach ($checks as $checkClass) {
            $checkResult = $this->runCheck($checkClass, $context);
            $findings = $findings->merge($checkResult->findings);
        }

        $report = new InspectionReport($inspection, $findings);

        Event::dispatch(new InspectionCompleted($report));

        return $report;
    }

    /**
     * @param class-string<Check> $checkClass
     * @param InspectionContext $context
     * @return CheckResult
     */
    private function runCheck(string $checkClass, InspectionContext $context): CheckResult
    {
        try {
            /** @var Check $check */
            $check = resolve($checkClass);
            return $check->apply($context);
        } catch (Throwable $e) {
            $finding = Finding::fatal(
                "Check execution failed: " . $e->getMessage(),
                $checkClass,
                $context->inspection->url(),
                details: ['exception' => get_class($e)]
            );
            return new CheckResult(new Collection([$finding]));
        }
    }

    public function record(InspectionReport $report): void
    {
        $inspectable = $report->inspection->model();

        Remark::query()
            ->where('inspectable_type', $inspectable->getMorphClass())
            ->where('inspectable_id', $inspectable->getKey())
            ->delete();

        // 4. Persist each new finding as a Remark.
        foreach ($report->findings as $finding) {
            $this->createInspectionRemark($inspectable, $finding);
        }

        // 5. Calculate and persist the final summary report.
        $this->createInspectionReport($inspectable, $report->findings);
    }

    private function createInspectionRemark(Model $inspectable, Finding $finding): void
    {
        Remark::query()
            ->create([
                'inspectable_type' => $inspectable->getMorphClass(),
                'inspectable_id' => $inspectable->getKey(),
                'level' => $finding->level,
                'message' => $finding->message,
                'check' => class_basename($finding->checkClass),
                'url' => $finding->url,
                'checklist' => $finding->checkClass::checklist(),
                'config' => $finding->config,
                'details' => $finding->details,
            ]);
    }

    private function createInspectionReport(Model $inspectable, Collection $findings): void
    {
        // Calculate overall status
        $overallStatus = RemarkLevel::SUCCESS;
        foreach ($findings as $finding) {
            if ($finding->level->getSeverity() > $overallStatus->getSeverity()) {
                $overallStatus = $finding->level;
            }
        }

        // Calculate counts per level for all findings
        $findingLevelCounts = $findings->countBy(fn(Finding $dto) => $dto->level->value)->all();
        $findingCountsDto = LevelCounts::fromArray($findingLevelCounts);

        // Calculate counts per level for checks (most severe)
        $checkLevelCounts = $findings
            ->groupBy('checkName')
            ->map(function (Collection $findingsForCheck) {
                $mostSevere = RemarkLevel::SUCCESS;
                foreach ($findingsForCheck as $finding) {
                    if ($finding->level->getSeverity() > $mostSevere->getSeverity()) {
                        $mostSevere = $finding->level;
                    }
                }
                return $mostSevere->value;
            })
            ->countBy()
            ->all();
        $checkCountsDto = LevelCounts::fromArray($checkLevelCounts);

        Report::updateOrCreate(
            [
                'inspectable_id' => $inspectable->getKey(),
                'inspectable_type' => $inspectable->getMorphClass(),
            ],
            [
                'level' => $overallStatus,
                'finding_counts' => $findingCountsDto,
                'check_counts' => $checkCountsDto,
            ]
        );
    }

    public function inspectionFor(Model $model): Inspection
    {
        if (property_exists($model, 'inspectionDefinition')) {
            return resolve($model::$inspectionDefinition, [
                'model' => $model,
            ]);
        }

        if (method_exists($model, 'inspection')) {
            return $model->inspection();
        }

        return resolve('App\\Syndicate\\Inspector\\Inspections\\' . class_basename($model) . 'Inspection', [
            'model' => $model,
        ]);
    }
}
