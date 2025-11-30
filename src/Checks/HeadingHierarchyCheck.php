<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class HeadingHierarchyCheck extends BaseCheck
{
    protected string $checklist = 'Content';
    // --- Configuration Properties ---
    protected RemarkLevel $level = RemarkLevel::WARNING;

    // -----------------------------

    /**
     * Checks if heading tags (h1-h6) follow a logical hierarchy.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $crawler = $this->context->crawler();
            $headingNodes = $crawler->filter('h1, h2, h3, h4, h5, h6');

            if ($headingNodes->count() === 0) {
                return $this->success('No headings present to check.');
            }

            // Rule 1: The first heading must be an H1.
            $firstHeadingTag = $headingNodes->first()->nodeName();
            if ($firstHeadingTag !== 'h1') {
                return $this->result([
                    $this->finding(
                        $this->level,
                        "Heading hierarchy error: The first heading on the page should be an <h1> but found a <$firstHeadingTag>.",
                        [
                            'issue_type' => 'incorrect_first_heading',
                            'found_tag' => $firstHeadingTag,
                            'expected_tag' => 'h1',
                        ]
                    )
                ]);
            }

            // Rule 2: Heading levels should not be skipped.
            $lastLevel = 1;
            foreach ($headingNodes as $index => $node) {
                if ($index === 0) {
                    continue;
                }

                $currentLevel = (int)substr($node->nodeName, 1);

                if ($currentLevel > ($lastLevel + 1)) {
                    $violatingTag = "h$currentLevel";
                    $previousTag = "h$lastLevel";

                    return $this->result([
                        $this->finding(
                            $this->level,
                            "Heading hierarchy error: A <$violatingTag> was found following a <$previousTag>, skipping a level.",
                            [
                                'issue_type' => 'skipped_level',
                                'violating_tag' => $violatingTag,
                                'violating_text' => trim($node->textContent),
                                'previous_tag' => $previousTag,
                            ]
                        )
                    ]);
                }

                $lastLevel = $currentLevel;
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during heading hierarchy check: " . $e->getMessage())
            ]);
        }

        return $this->success('Heading hierarchy is valid.');
    }
}
