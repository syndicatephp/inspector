<?php

namespace Syndicate\Inspector\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Syndicate\Inspector\DTOs\ModelInspectionReport;

class BulkInspectionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ModelInspectionReport $modelInspectionReport,
        public float $totalTimeSeconds = 0.0,
        public float $averageTimePerInspection = 0.0
    ) {
    }
}
