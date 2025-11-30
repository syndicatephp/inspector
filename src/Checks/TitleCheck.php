<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class TitleCheck extends BaseCheck
{
    protected string $checklist = 'Baseline';
    // --- Configuration Properties ---
    protected ?int $minTitleLength = 10;
    protected ?int $maxTitleLength = 60;
    protected int $lengthWarningOverage = 15;
    protected RemarkLevel $minorLengthDeviationLevel = RemarkLevel::NOTICE;
    protected RemarkLevel $majorLengthDeviationLevel = RemarkLevel::WARNING;
    protected RemarkLevel $missingEmptyLevel = RemarkLevel::ERROR;
    protected RemarkLevel $multipleLevel = RemarkLevel::ERROR;

    // -----------------------------

    /**
     * Check for the presence, uniqueness, and quality of the <title> tag.
     */
    protected function applyCheck(): CheckResult
    {
        $findings = [];

        try {
            $crawler = $this->context->crawler();
            $titleNodes = $crawler->filter('head > title');
            $titleNodeCount = $titleNodes->count();

            if ($titleNodeCount === 0) {
                $findings[] = $this->finding($this->missingEmptyLevel, 'Missing <title> tag.', ['issue_type' => 'missing']);
                return $this->result($findings);
            }

            if ($titleNodeCount > 1) {
                $findings[] = $this->finding($this->multipleLevel, 'Multiple <title> tags found.', ['issue_type' => 'multiple', 'count' => $titleNodeCount]);
            }

            $titleContent = trim($titleNodes->first()->text());
            if (empty($titleContent)) {
                $findings[] = $this->finding($this->missingEmptyLevel, '<title> tag is empty or contains only whitespace.', ['issue_type' => 'empty']);
            } else {
                $this->checkLength($findings, $titleContent);
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during title check: " . $e->getMessage())
            ]);
        }

        return $findings ? $this->result($findings) : $this->success('Title is present and has appropriate length.');
    }

    /**
     * Internal helper to check min/max length and add tiered findings.
     */
    private function checkLength(array &$findings, string $titleContent): void
    {
        $titleLength = mb_strlen($titleContent);

        // --- Tiered Logic for Maximum Length ---
        if ($this->maxTitleLength !== null && $titleLength > $this->maxTitleLength) {
            $overage = $titleLength - $this->maxTitleLength;

            $level = ($overage >= $this->lengthWarningOverage) ? $this->majorLengthDeviationLevel : $this->minorLengthDeviationLevel;

            $message = "Title length ($titleLength) exceeds the ideal maximum of $this->maxTitleLength by $overage characters.";

            $findings[] = $this->finding(
                $level,
                $message,
                [
                    'issue_type' => 'length_max',
                    'title' => $titleContent,
                    'length' => $titleLength,
                    'limit' => $this->maxTitleLength,
                    'overage' => $overage,
                ]
            );
        }

        // --- Standard Logic for Minimum Length ---
        if ($this->minTitleLength !== null && $titleLength < $this->minTitleLength) {
            $findings[] = $this->finding(
                $this->majorLengthDeviationLevel,
                "Title length ($titleLength) is less than the recommended minimum of $this->minTitleLength.",
                [
                    'issue_type' => 'length_min',
                    'title' => $titleContent,
                    'length' => $titleLength,
                    'limit' => $this->minTitleLength,
                ]
            );
        }
    }
}
