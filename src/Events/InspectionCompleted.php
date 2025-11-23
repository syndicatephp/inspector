<?php

namespace Syndicate\Inspector\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Syndicate\Inspector\DTOs\InspectionReport;

class InspectionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public InspectionReport $inspectionReport,
    ) {
    }
}
