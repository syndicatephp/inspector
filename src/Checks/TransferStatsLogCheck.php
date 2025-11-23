<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class TransferStatsLogCheck extends BaseCheck
{
    // --- Configuration Properties ---
    protected RemarkLevel $level = RemarkLevel::INFO;

    // -----------------------------

    public static function checklist(): string
    {
        return 'Performance';
    }

    /**
     * Logs the HTTP transfer statistics for the request.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $stats = $this->context->response->transferStats;

            // Case 1: Stats are not available at all.
            if (!$stats) {
                return $this->success(
                    'Transfer stats were not collected for this request.',
                    ['note' => 'To enable, make the request using Http::withStats()->...']
                );
            }

            // Case 2: Stats are available.
            $allStats = $stats->getHandlerStats();
            $totalTimeMs = isset($allStats['total_time']) ? round($allStats['total_time'] * 1000) : 'N/A';

            return $this->result([
                $this->finding(
                    $this->level,
                    "Request completed in {$totalTimeMs}ms.",
                    $allStats
                )
            ]);

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR,
                    "Error during transfer stats logging check: " . $e->getMessage())
            ]);
        }
    }
}
