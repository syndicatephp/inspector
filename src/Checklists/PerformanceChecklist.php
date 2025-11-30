<?php

namespace Syndicate\Inspector\Checklists;

use Syndicate\Inspector\Checks\DocumentSizeCheck;
use Syndicate\Inspector\Checks\DomSizeCheck;
use Syndicate\Inspector\Checks\PerformanceTimingsCheck;
use Syndicate\Inspector\Checks\TransferStatsLogCheck;
use Syndicate\Inspector\Contracts\Check;

class PerformanceChecklist
{
    /**
     * @return Check[]
     */
    public static function checks(): array
    {
        return [
            PerformanceTimingsCheck::make(),
            DocumentSizeCheck::make(),
            DomSizeCheck::make(),
            TransferStatsLogCheck::make(),
        ];
    }
}
