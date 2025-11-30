<?php

namespace Syndicate\Inspector\Checks;

use DOMElement;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class HreflangCheck extends BaseCheck
{
    protected string $checklist = 'SEO';
    // --- Configuration Properties ---
    protected RemarkLevel $formatLevel = RemarkLevel::ERROR;
    protected RemarkLevel $relativeUrlLevel = RemarkLevel::ERROR;
    protected RemarkLevel $missingSelfReferenceLevel = RemarkLevel::WARNING;

    // -----------------------------

    /**
     * Checks for the correct implementation of hreflang link tags.
     */
    protected function applyCheck(): CheckResult
    {
        $findings = [];
        try {
            $crawler = $this->context->crawler();
            $hreflangNodes = $crawler->filter('head > link[rel="alternate"][hreflang]');

            // If there are no hreflang tags, there's nothing to check. This isn't an error.
            if ($hreflangNodes->count() === 0) {
                return $this->success('No hreflang tags found on the page.');
            }

            $languageCodes = [];
            $hasSelfReference = false;

            // 1. First, iterate through all found hreflang tags and validate each one individually.
            /** @var DOMElement $node */
            foreach ($hreflangNodes as $node) {
                $hreflang = trim($node->getAttribute('hreflang'));
                $href = trim($node->getAttribute('href'));

                // Validate the hreflang attribute format (e.g., 'en', 'en-US', 'x-default').
                // A simple regex for the most common formats.
                if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$|^x-default$/', $hreflang)) {
                    $findings[] = $this->finding($this->formatLevel, "Hreflang attribute '$hreflang' has an invalid format.", ['issue_type' => 'invalid_format', 'hreflang' => $hreflang, 'href' => $href]);
                }

                // Validate the href attribute (must be absolute).
                if (!str_starts_with($href, 'http://') && !str_starts_with($href, 'https://')) {
                    $findings[] = $this->finding($this->relativeUrlLevel, "Hreflang link for '$hreflang' must use an absolute URL.", ['issue_type' => 'relative_url', 'hreflang' => $hreflang, 'href' => $href]);
                }

                // Check for duplicate language codes.
                if (isset($languageCodes[$hreflang])) {
                    $findings[] = $this->finding($this->formatLevel, "Duplicate hreflang tag found for language code '$hreflang'.", ['issue_type' => 'duplicate_code', 'hreflang' => $hreflang]);
                }
                $languageCodes[$hreflang] = $href;

                // Check if this tag is a self-referencing canonical.
                if ($href === $this->context->inspection->url()) {
                    $hasSelfReference = true;
                }
            }

            // 2. After checking all tags, perform a check for the set as a whole.
            // It's a best practice for the set of hreflang tags to include a self-reference.
            if (!$hasSelfReference) {
                $findings[] = $this->finding(
                    $this->missingSelfReferenceLevel,
                    'Hreflang tags are present, but a self-referencing link pointing to the current URL is missing.',
                    ['issue_type' => 'missing_self_reference', 'current_url' => $this->context->inspection->url()]
                );
            }

            return $findings ? $this->result($findings) : $this->success('All hreflang tags are present and correctly configured.');

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during hreflang check: " . $e->getMessage())
            ]);
        }
    }
}
