<?php

namespace Syndicate\Inspector\DTOs;

use Illuminate\Support\Collection;
use Syndicate\Inspector\Contracts\Inspection;
use Syndicate\Inspector\Enums\RemarkLevel;

readonly class InspectionReport
{
    public RemarkLevel $status;
    public LevelCounts $findingCounts;
    public LevelCounts $checkCounts;

    /**
     * @param Inspection $inspection
     * @param Collection<int, CheckResult> $results
     */
    public function __construct(
        public Inspection $inspection,
        public Collection $results
    )
    {
        $this->status = $this->calculateOverallStatus();
        $this->findingCounts = $this->calculateFindingCounts();
        $this->checkCounts = $this->calculateCheckCounts();
    }

    /**
     * The overall status is determined by the most severe status of any check result.
     */
    private function calculateOverallStatus(): RemarkLevel
    {
        $mostSevere = RemarkLevel::SUCCESS;

        foreach ($this->results as $result) {
            if ($result->status->getSeverity() > $mostSevere->getSeverity()) {
                $mostSevere = $result->status;
            }
        }

        return $mostSevere;
    }

    /**
     * Flattens all findings from all results to get total counts per level.
     */
    private function calculateFindingCounts(): LevelCounts
    {
        // We use flatMap to retrieve all nested findings into one collection
        $allFindings = $this->results->flatMap(fn(CheckResult $r) => $r->findings);

        $counts = $allFindings->countBy(fn(Finding $f) => $f->level->value)->all();

        return LevelCounts::fromArray($counts);
    }

    /**
     * Counts how many Checks ended up with which status.
     * (e.g., 5 Success, 1 Error)
     */
    private function calculateCheckCounts(): LevelCounts
    {
        $counts = $this->results->countBy(fn(CheckResult $r) => $r->status->value)->all();

        return LevelCounts::fromArray($counts);
    }
}
