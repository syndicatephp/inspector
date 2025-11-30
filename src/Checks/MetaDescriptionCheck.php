<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class MetaDescriptionCheck extends BaseCheck
{
    protected string $checklist = 'SEO';
    // --- Configuration Properties ---
    protected ?int $minDescriptionLength = 50;
    protected ?int $maxDescriptionLength = 160;
    protected ?int $lengthWarningOverage = 20;
    protected RemarkLevel $minorLengthDeviationLevel = RemarkLevel::NOTICE;
    protected RemarkLevel $majorLengthDeviationLevel = RemarkLevel::WARNING;
    protected RemarkLevel $missingEmptyLevel = RemarkLevel::ERROR;
    protected RemarkLevel $multipleLevel = RemarkLevel::ERROR;

    // -----------------------------

    /**
     * Check for the presence, uniqueness, and quality of the <meta name="description"> tag.
     */
    protected function applyCheck(): CheckResult
    {
        $findings = [];

        try {
            $crawler = $this->context->crawler();
            $descNodes = $crawler->filter('head > meta[name="description"]');
            $descNodeCount = $descNodes->count();

            // 1. Check for Missing Description
            if ($descNodeCount === 0) {
                $findings[] = $this->finding(
                    $this->missingEmptyLevel,
                    'Missing <meta name="description"> tag.',
                    ['issue_type' => 'missing']
                );
                return $this->result($findings);
            }

            // 2. Check for Multiple Descriptions
            if ($descNodeCount > 1) {
                $findings[] = $this->finding(
                    $this->multipleLevel,
                    'Multiple <meta name="description"> tags found.',
                    ['issue_type' => 'multiple', 'count' => $descNodeCount]
                );
            }

            // 3. Check Content and Length of the first description tag
            $descriptionContent = trim($descNodes->first()->attr('content') ?? '');
            if (empty($descriptionContent)) {
                $findings[] = $this->finding(
                    $this->missingEmptyLevel,
                    '<meta name="description"> tag content is empty.',
                    ['issue_type' => 'empty']
                );
            } else {
                $this->checkLength($findings, $descriptionContent);
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during meta description check: " . $e->getMessage())
            ]);
        }

        return $findings ? $this->result($findings) : $this->success('Meta description is present and has appropriate length.');
    }

    /**
     * Internal helper to check min/max length and add findings.
     */
    private function checkLength(array &$findings, string $descriptionContent): void
    {
        $descLength = mb_strlen($descriptionContent);

        // --- Tiered Logic for Maximum Length ---
        if ($this->maxDescriptionLength !== null && $descLength > $this->maxDescriptionLength) {
            $overage = $descLength - $this->maxDescriptionLength;

            $level = ($overage >= $this->lengthWarningOverage) ? $this->majorLengthDeviationLevel : $this->minorLengthDeviationLevel;

            $message = "Title length ($descLength) exceeds the ideal maximum of $this->maxDescriptionLength by $overage characters.";

            $findings[] = $this->finding(
                $level,
                $message,
                [
                    'issue_type' => 'length_max',
                    'title' => $descriptionContent,
                    'length' => $descLength,
                    'limit' => $this->maxDescriptionLength,
                    'overage' => $overage,
                ]
            );
        }

        // --- Standard Logic for Minimum Length ---
        if ($this->minDescriptionLength !== null && $descLength < $this->minDescriptionLength) {
            $findings[] = $this->finding(
                $this->majorLengthDeviationLevel,
                "Title length ($descLength) is less than the recommended minimum of $this->minDescriptionLength.",
                [
                    'issue_type' => 'length_min',
                    'title' => $descriptionContent,
                    'length' => $descLength,
                    'limit' => $this->minDescriptionLength,
                ]
            );
        }
    }
}
