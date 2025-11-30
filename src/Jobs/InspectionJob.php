<?php

namespace Syndicate\Inspector\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Syndicate\Inspector\Contracts\Inspection;
use Syndicate\Inspector\Services\InspectorService;

class InspectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 3;

    public function __construct(
        public Inspection $inspection,
    )
    {
    }

    public function handle(InspectorService $inspectorService): void
    {
        $inspectorService->runAndRecord($this->inspection);
    }
}
