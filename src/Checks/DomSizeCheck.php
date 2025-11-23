<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class DomSizeCheck extends BaseCheck
{
    // --- Configuration Properties ---
    protected int $noticeThreshold = 1000;
    protected int $warningThreshold = 2000;
    protected int $errorThreshold = 3000;

    // -----------------------------

    public static function checklist(): string
    {
        return 'Performance';
    }

    /**
     * Check the overall dom size of the page.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $crawler = $this->context->crawler();
            $domNodeCount = $crawler->filter('*')->count();

            // Rule 1: Error Threshold
            if ($domNodeCount > $this->errorThreshold) {
                return $this->result([
                    $this->finding(
                        RemarkLevel::ERROR,
                        "DOM size is critically large ($domNodeCount elements), exceeding the error threshold of $this->errorThreshold.",
                        [
                            'issue_type' => 'critical_size',
                            'node_count' => $domNodeCount,
                            'threshold' => $this->errorThreshold,
                        ]
                    )
                ]);
            }

            // Rule 2: Warning Threshold
            if ($domNodeCount > $this->warningThreshold) {
                return $this->result([
                    $this->finding(
                        RemarkLevel::WARNING,
                        "DOM size is large ($domNodeCount elements), exceeding the warning threshold of $this->warningThreshold.",
                        [
                            'issue_type' => 'large_size',
                            'node_count' => $domNodeCount,
                            'threshold' => $this->warningThreshold,
                        ]
                    )
                ]);
            }

            // Rule 3: Notice Threshold
            if ($domNodeCount > $this->noticeThreshold) {
                return $this->result([
                    $this->finding(
                        RemarkLevel::NOTICE,
                        "DOM size is large ($domNodeCount elements), exceeding the notice threshold of $this->noticeThreshold.",
                        [
                            'issue_type' => 'large_size',
                            'node_count' => $domNodeCount,
                            'threshold' => $this->noticeThreshold,
                        ]
                    )
                ]);
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during DOM size check: " . $e->getMessage())
            ]);
        }

        $message = "DOM size ($domNodeCount elements) is within acceptable limits.";
        $details = ['node_count' => $domNodeCount];

        return $this->success($message, $details);
    }
}
