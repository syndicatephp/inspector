<?php

namespace Syndicate\Inspector\DTOs;

use Illuminate\Support\Collection;
use Syndicate\Inspector\Enums\RemarkLevel;

readonly class CheckResult
{
    /** @param Collection<int, Finding> $findings */
    public function __construct(public Collection $findings)
    {
    }

    /**
     * @param Finding[] $findings
     */
    public static function from(array $findings): self
    {
        return new self(new Collection($findings));
    }

    public function isSuccess(): bool
    {
        return $this->findings->isEmpty();
    }

    public function hasFindings(): bool
    {
        return !$this->findings->isEmpty();
    }

    public function count(): int
    {
        return $this->findings->count();
    }

    public function getHighestSeverity(): ?RemarkLevel
    {
        if ($this->findings->isEmpty()) {
            return null;
        }

        return $this->findings
            ->map(fn(Finding $finding) => $finding->level)
            ->sortByDesc(fn(RemarkLevel $level) => $level->getSeverity())
            ->first();
    }

    public function getFindingsByLevel(RemarkLevel $level): Collection
    {
        return $this->findings->filter(fn(Finding $finding) => $finding->level === $level);
    }
}
