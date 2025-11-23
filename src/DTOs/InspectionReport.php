<?php

namespace Syndicate\Inspector\DTOs;

use Illuminate\Support\Collection;
use Syndicate\Inspector\Contracts\Inspection;
use Syndicate\Inspector\Enums\RemarkLevel;

readonly class InspectionReport
{
    public RemarkLevel $status;
    public LevelCounts $findingCounts;

    /**
     * @param Collection<int,Finding> $findings
     */
    public function __construct(
        public Inspection $inspection,
        public Collection $findings
    )
    {
        $this->status = $this->calculateOverallStatus();
        $this->findingCounts = $this->calculateFindingCounts();
    }

    private function calculateOverallStatus(): RemarkLevel
    {
        $mostSevere = RemarkLevel::SUCCESS;
        foreach ($this->findings as $finding) {
            /** @var Finding $finding */
            if ($finding->level->getSeverity() > $mostSevere->getSeverity()) {
                $mostSevere = $finding->level;
            }
        }
        return $mostSevere;
    }

    private function calculateFindingCounts(): LevelCounts
    {
        $counts = $this->findings->countBy(fn(Finding $dto) => $dto->level->value)->all();
        return LevelCounts::fromArray($counts);
    }
}
