<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class DocumentSizeCheck extends BaseCheck
{
    // --- Configuration Properties ---
    protected int $noticeThresholdKb = 250;
    protected int $warningThresholdKb = 500;
    protected int $errorThresholdKb = 1000;
    protected RemarkLevel $level = RemarkLevel::WARNING;

    // -----------------------------

    public static function checklist(): string
    {
        return 'Performance';
    }

    /**
     * Checks the uncompressed size of the response body against configured thresholds.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $body = $this->context->response->body();

            $actualSizeBytes = strlen($body);
            $actualSizeKb = round($actualSizeBytes / 1024, 2);

            $errorThresholdBytes = $this->errorThresholdKb * 1024;
            $warningThresholdBytes = $this->warningThresholdKb * 1024;
            $noticeThresholdBytes = $this->noticeThresholdKb * 1024;

            // 1. Check against the highest threshold (error) first.
            if ($actualSizeBytes > $errorThresholdBytes) {
                return $this->result([
                    $this->finding(
                        RemarkLevel::ERROR,
                        "Uncompressed response size ($actualSizeKb KB) is critically large, exceeding the error threshold of $this->errorThresholdKb KB.",
                        [
                            'issue_type' => 'critical_size',
                            'size_kb' => $actualSizeKb,
                            'threshold_kb' => $this->errorThresholdKb,
                        ]
                    )
                ]);
            }

            // 2. Then check against the warning threshold.
            if ($actualSizeBytes > $warningThresholdBytes) {
                return $this->result([
                    $this->finding(
                        RemarkLevel::WARNING,
                        "Uncompressed response size ($actualSizeKb KB) is large, exceeding the warning threshold of $this->warningThresholdKb KB.",
                        [
                            'issue_type' => 'large_size',
                            'size_kb' => $actualSizeKb,
                            'threshold_kb' => $this->warningThresholdKb,
                        ]
                    )
                ]);
            }

            // 3. Then check against the notice threshold.
            if ($actualSizeBytes > $noticeThresholdBytes) {
                return $this->result([
                    $this->finding(
                        RemarkLevel::NOTICE,
                        "Uncompressed response size ($actualSizeKb KB) is large, exceeding the notice threshold of $this->noticeThresholdKb KB.",
                        [
                            'issue_type' => 'large_size',
                            'size_kb' => $actualSizeKb,
                            'threshold_kb' => $this->noticeThresholdKb,
                        ]
                    )
                ]);
            }

            // 4. If no thresholds were exceeded, it's a success.
            return $this->success(
                "Uncompressed response size ($actualSizeKb KB) is within acceptable limits.",
                ['size_kb' => $actualSizeKb]
            );

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during response size check: " . $e->getMessage())
            ]);
        }
    }
}
