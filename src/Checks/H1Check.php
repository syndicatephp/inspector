<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class H1Check extends BaseCheck
{
    // --- Configuration Properties ---
    protected ?int $maxHeadingLength = 70;
    protected ?int $minHeadingLength = 20;
    protected ?int $lengthWarningOverage = 15;
    protected RemarkLevel $minorLengthDeviationLevel = RemarkLevel::NOTICE;
    protected RemarkLevel $majorLengthDeviationLevel = RemarkLevel::WARNING;
    protected RemarkLevel $multipleLevel = RemarkLevel::ERROR;
    protected RemarkLevel $missingEmptyLevel = RemarkLevel::ERROR;

    // -----------------------------

    public static function checklist(): string
    {
        return 'Content';
    }

    /**
     * Check for the presence, uniqueness, and basic quality of the <h1> tag.
     */
    protected function applyCheck(): CheckResult
    {
        $findings = [];

        try {
            $crawler = $this->context->crawler();
            $headingNodes = $crawler->filter('h1');
            $headingNodeCount = $headingNodes->count();

            // 1. Check for Missing Heading
            if ($headingNodeCount === 0) {
                $findings[] = $this->finding(
                    $this->missingEmptyLevel,
                    'Missing <h1> tag.',
                    ['issue_type' => 'missing']
                );
                return $this->result($findings);
            }

            // 2. Check for Multiple Headings
            if ($headingNodeCount > 1) {
                $findings[] = $this->finding(
                    $this->multipleLevel,
                    'Multiple <h1> tags found.',
                    ['issue_type' => 'multiple', 'count' => $headingNodeCount]
                );
            }

            // 3. Check Content and Length
            $headingContent = trim($headingNodes->first()->text());
            if (empty($headingContent)) {
                $findings[] = $this->finding(
                    $this->missingEmptyLevel,
                    '<h1> tag is empty or contains only whitespace.',
                    ['issue_type' => 'empty']
                );
            } else {
                $this->checkLength($findings, $headingContent);
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during heading check: " . $e->getMessage())
            ]);
        }

        return $findings ? $this->result($findings) : $this->success('Heading is present and has appropriate length.');
    }

    private function checkLength(array &$findings, string $headingContent): void
    {
        $headingLength = mb_strlen($headingContent);

        // --- Tiered Logic for Maximum Length ---
        if ($this->maxHeadingLength !== null && $headingLength > $this->maxHeadingLength) {
            $overage = $headingLength - $this->maxHeadingLength;

            $level = ($overage >= $this->lengthWarningOverage) ? $this->majorLengthDeviationLevel : $this->minorLengthDeviationLevel;

            $message = "Title length ($headingLength) exceeds the ideal maximum of $this->maxHeadingLength by $overage characters.";

            $findings[] = $this->finding(
                $level,
                $message,
                [
                    'issue_type' => 'length_max',
                    'title' => $headingContent,
                    'length' => $headingLength,
                    'limit' => $this->maxHeadingLength,
                    'overage' => $overage,
                ]
            );
        }

        // --- Standard Logic for Minimum Length ---
        if ($this->minHeadingLength !== null && $headingLength < $this->minHeadingLength) {
            $findings[] = $this->finding(
                $this->majorLengthDeviationLevel,
                "Title length ($headingLength) is less than the recommended minimum of $this->minHeadingLength.",
                [
                    'issue_type' => 'length_min',
                    'title' => $headingContent,
                    'length' => $headingLength,
                    'limit' => $this->minHeadingLength,
                ]
            );
        }
    }
}
