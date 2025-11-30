<?php

namespace Syndicate\Inspector\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use LogicException;
use Syndicate\Inspector\Checks\HttpRequestError;
use Syndicate\Inspector\Contracts\Check;
use Syndicate\Inspector\Contracts\Inspection;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\DTOs\Finding;
use Syndicate\Inspector\DTOs\InspectionContext;
use Syndicate\Inspector\DTOs\InspectionReport;
use Syndicate\Inspector\Events\InspectionCompleted;
use Syndicate\Inspector\Inspections\UrlInspection;
use Syndicate\Inspector\Models\Report;
use Throwable;

class InspectorService
{
    public function inspectModel(Model $model): void
    {
        if (!method_exists($model, 'inspection')) {
            throw new LogicException("Model must use Inspectable trait");
        }

        $this->runAndRecord($model->inspection());
    }

    public function runAndRecord(Inspection $inspection): void
    {
        $report = $this->run($inspection);

        if ($report)
            $this->record($report);
    }

    public function run(Inspection $inspection): ?InspectionReport
    {
        if (!$inspection->shouldInspect()) return null;

        $url = $inspection->url();
        $httpOptions = $inspection->httpOptions();
        $checks = $inspection->checks();
        $results = new Collection();

        try {
            $response = Http::withOptions($httpOptions)->get($url);
        } catch (Throwable $e) {
            $httpFinding = Finding::fatal('HTTP request failed: ' . $e->getMessage());
            $result = new CheckResult(new HttpRequestError(), collect([$httpFinding]));
            return new InspectionReport($inspection, collect([$result]));
        }

        $context = InspectionContext::make($inspection, $response);

        foreach ($checks as $check) {
            $checkResult = $this->runCheck($check, $context);
            $results = $results->add($checkResult);
        }

        $report = new InspectionReport($inspection, $results);

        Event::dispatch(new InspectionCompleted($report));

        return $report;
    }

    private function runCheck(Check $check, InspectionContext $context): CheckResult
    {
        try {
            return $check->apply($context);
        } catch (Throwable $e) {
            $finding = Finding::fatal(
                message: "Check execution failed: " . $e->getMessage(),
                details: ['exception' => get_class($e)]
            );
            return new CheckResult($check, new Collection([$finding]));
        }
    }

    public function record(InspectionReport $reportDTO): void
    {
        $dbReport = $this->createInspectionReport($reportDTO);

        $dbReport->remarks()->delete();

        foreach ($reportDTO->results as $result) {
            $this->createInspectionRemark($dbReport, $result);
        }
    }

    private function createInspectionReport(InspectionReport $reportDTO): Report
    {
        $model = $reportDTO->inspection->model();
        $url = $reportDTO->inspection->url();

        return Report::updateOrCreate(
            $model
                ? ['inspectable_type' => $model->getMorphClass(), 'inspectable_id' => $model->getKey()]
                : ['inspectable_type' => null, 'inspectable_id' => null, 'url' => $url],
            [
                'level' => $reportDTO->status, // Calculated in DTO
                'finding_counts' => $reportDTO->findingCounts, // Calculated in DTO
                'check_counts' => $reportDTO->checkCounts, // Calculated in DTO
                'url' => $url,
            ]
        );
    }

    private function createInspectionRemark(Report $report, CheckResult $result): void
    {
        foreach ($result->findings as $finding) {
            /** @var Finding $finding */
            $report->remarks()->create([
                // Report Data
                'inspectable_type' => $report->inspectable_type,
                'inspectable_id' => $report->inspectable_id,
                'url' => $report->url,

                // Finding Data
                'level' => $finding->level,
                'message' => $finding->message,
                'details' => $finding->details,

                // Check Data
                'check' => $result->check->getName(),
                'checklist' => $result->check->getChecklist(),
                'config' => $result->check->getConfig(),
            ]);
        }
    }

    public function inspectUrl(string $url): void
    {
        $this->runAndRecord(new UrlInspection($url));
    }
}
