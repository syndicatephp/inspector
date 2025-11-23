<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class ViewportCheck extends BaseCheck
{
    // --- Configuration Properties ---
    protected RemarkLevel $missingLevel = RemarkLevel::ERROR;
    protected RemarkLevel $misconfiguredLevel = RemarkLevel::ERROR;

    // -----------------------------

    public static function checklist(): string
    {
        return 'Baseline';
    }

    /**
     * Checks for the presence and correct configuration of the viewport meta-tag.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $crawler = $this->context->crawler();
            $viewportNode = $crawler->filter('head > meta[name="viewport"]');

            // 1. Check for missing viewport tag.
            if ($viewportNode->count() === 0) {
                return $this->result([
                    $this->finding(
                        $this->missingLevel,
                        'The viewport meta tag (<meta name="viewport">) is missing.',
                        ['issue_type' => 'missing']
                    )
                ]);
            }

            $content = $viewportNode->first()->attr('content');

            if (empty($content)) {
                return $this->result([
                    $this->finding(
                        $this->misconfiguredLevel,
                        'The viewport meta tag has an empty content attribute.',
                        ['issue_type' => 'empty_content']
                    )
                ]);
            }

            $findings = [];

            // 2. Check for 'width=device-width'.
            if (!str_contains($content, 'width=device-width')) {
                $findings[] = $this->finding(
                    $this->misconfiguredLevel,
                    "Viewport 'content' attribute is missing the required 'width=device-width' directive.",
                    ['issue_type' => 'missing_width', 'content' => $content]
                );
            }

            // 3. Check for 'initial-scale=1'.
            if (!preg_match('/initial-scale\s*=\s*1(\.0)?/', $content)) {
                $findings[] = $this->finding(
                    $this->misconfiguredLevel,
                    "Viewport 'content' attribute is missing the required 'initial-scale=1.0' directive.",
                    ['issue_type' => 'missing_initial_scale', 'content' => $content]
                );
            }

            return $findings ? $this->result($findings) : $this->success('The viewport meta tag is present and correctly configured.');

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during viewport check: " . $e->getMessage())
            ]);
        }
    }
}
