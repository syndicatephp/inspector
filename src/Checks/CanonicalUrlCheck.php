<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class CanonicalUrlCheck extends BaseCheck
{
    protected string $checklist = 'SEO';
    // --- Configuration Properties ---
    protected RemarkLevel $missingLevel = RemarkLevel::WARNING;
    protected RemarkLevel $multipleLevel = RemarkLevel::ERROR;
    protected RemarkLevel $invalidUrlLevel = RemarkLevel::ERROR;

    // -----------------------------

    /**
     * Checks for the presence and validity of the canonical link tag.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $crawler = $this->context->crawler();
            $canonicalNodes = $crawler->filter('head > link[rel="canonical"]');
            $nodeCount = $canonicalNodes->count();

            // 1. Check for missing canonical tag.
            if ($nodeCount === 0) {
                return $this->result([
                    $this->finding(
                        $this->missingLevel,
                        'The canonical link tag (<link rel="canonical">) is missing.',
                        ['issue_type' => 'missing']
                    )
                ]);
            }

            // 2. Check for multiple canonical tags, which is a critical error.
            if ($nodeCount > 1) {
                return $this->result([
                    $this->finding(
                        $this->multipleLevel,
                        "Multiple canonical link tags found ($nodeCount). There must be exactly one.",
                        ['issue_type' => 'multiple', 'count' => $nodeCount]
                    )
                ]);
            }

            $href = trim($canonicalNodes->first()->attr('href') ?? '');

            // 3. Check if the href attribute is empty.
            if (empty($href)) {
                return $this->result([
                    $this->finding(
                        $this->invalidUrlLevel,
                        'The canonical link tag has an empty href attribute.',
                        ['issue_type' => 'empty_href']
                    )
                ]);
            }

            // 4. Check if the href is an absolute URL.
            if (!str_starts_with($href, 'http://') && !str_starts_with($href, 'https://')) {
                return $this->result([
                    $this->finding(
                        $this->invalidUrlLevel,
                        "The canonical link's href attribute must be an absolute URL.",
                        ['issue_type' => 'relative_url', 'href' => $href]
                    )
                ]);
            }

            // 5. Optional but good: check if the URL is a validly formed URL.
            if (filter_var($href, FILTER_VALIDATE_URL) === false) {
                return $this->result([
                    $this->finding(
                        $this->invalidUrlLevel,
                        'The canonical link has a malformed URL in its href attribute.',
                        ['issue_type' => 'malformed_url', 'href' => $href]
                    )
                ]);
            }

            return $this->success('The canonical link tag is present and valid.');

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during canonical URL check: " . $e->getMessage())
            ]);
        }
    }
}
