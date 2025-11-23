<?php

namespace Syndicate\Inspector\DTOs;

use Syndicate\Inspector\Enums\RemarkLevel;

readonly class LevelCounts
{
    public function __construct(
        public int $success = 0,
        public int $info = 0,
        public int $notice = 0,
        public int $warning = 0,
        public int $error = 0,
        public int $fatal = 0
    )
    {
    }

    public static function fromArray(array $counts): self
    {
        return new self(
            $counts[RemarkLevel::SUCCESS->value] ?? 0,
            $counts[RemarkLevel::INFO->value] ?? 0,
            $counts[RemarkLevel::NOTICE->value] ?? 0,
            $counts[RemarkLevel::WARNING->value] ?? 0,
            $counts[RemarkLevel::ERROR->value] ?? 0,
            $counts[RemarkLevel::FATAL->value] ?? 0,
        );
    }

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0, 0);
    }

    public function total(): int
    {
        return $this->success + $this->info + $this->notice + $this->warning + $this->error + $this->fatal;
    }

    public function toArray(): array
    {
        return [
            RemarkLevel::SUCCESS->value => $this->success,
            RemarkLevel::INFO->value => $this->info,
            RemarkLevel::NOTICE->value => $this->notice,
            RemarkLevel::WARNING->value => $this->warning,
            RemarkLevel::ERROR->value => $this->error,
            RemarkLevel::FATAL->value => $this->fatal,
        ];
    }
}
