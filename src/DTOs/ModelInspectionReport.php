<?php

namespace Syndicate\Inspector\DTOs;

final readonly class ModelInspectionReport
{
    public int $total;

    /**
     * @param class-string $modelClass The fully qualified class name of the model being summarized.
     */
    public function __construct(
        public string $modelClass,
        public int    $success,
        public int    $info,
        public int    $notice,
        public int    $warning,
        public int    $error,
        public int    $fatal,
    )
    {
        $this->total = $this->success + $this->info + $this->notice + $this->warning + $this->error + $this->fatal;
    }
}
