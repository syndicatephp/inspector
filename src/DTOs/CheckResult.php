<?php

namespace Syndicate\Inspector\DTOs;

use Illuminate\Support\Collection;
use Syndicate\Inspector\Contracts\Check;
use Syndicate\Inspector\Enums\RemarkLevel;

readonly class CheckResult
{
    public RemarkLevel $status;
    public LevelCounts $findingCounts;

    /**
     * @param Check $check The specific instance (with config state)
     * @param Collection<int, Finding> $findings
     */
    public function __construct(
        public Check      $check,
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

    public function isSuccess(): bool
    {
        return $this->findings->isEmpty() || $this->status === RemarkLevel::SUCCESS;
    }
}
