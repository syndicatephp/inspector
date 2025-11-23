<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class RobotsMetaCheck extends BaseCheck
{
    // --- Configuration Properties ---
    protected RemarkLevel $noindexLevel = RemarkLevel::INFO;
    protected RemarkLevel $nofollowLevel = RemarkLevel::INFO;
    protected RemarkLevel $multipleLevel = RemarkLevel::ERROR;

    // -----------------------------

    public static function checklist(): string
    {
        return 'SEO';
    }

    /**
     * Checks the robots meta tag for 'noindex' or 'nofollow' directives.
     */
    protected function applyCheck(): CheckResult
    {
        $findings = [];
        try {
            $crawler = $this->context->crawler();
            $robotsNodes = $crawler->filter('head > meta[name="robots"]');

            if ($robotsNodes->count() === 0) {
                return $this->success('No robots meta tag found; crawlers will use default behavior.');
            }

            if ($robotsNodes->count() > 1) {
                $findings[] = $this->finding(
                    $this->multipleLevel,
                    'Multiple robots meta tags found. Directives should be consolidated into one tag.',
                    ['issue_type' => 'multiple', 'count' => $robotsNodes->count()]
                );
            }

            $content = strtolower($robotsNodes->first()->attr('content') ?? '');

            // Check for noindex
            if (str_contains($content, 'noindex')) {
                $findings[] = $this->finding(
                    $this->noindexLevel,
                    "A 'noindex' directive was found in the robots meta tag, which will prevent this page from being indexed by search engines.",
                    ['issue_type' => 'noindex_found', 'content' => $content]
                );
            }

            // Check for nofollow
            if (str_contains($content, 'nofollow')) {
                $findings[] = $this->finding(
                    $this->nofollowLevel,
                    "A 'nofollow' directive was found in the robots meta tag, which will prevent search engines from following links on this page.",
                    ['issue_type' => 'nofollow_found', 'content' => $content]
                );
            }

            return $findings ? $this->result($findings) : $this->success("The robots meta tag is present and does not contain 'noindex' or 'nofollow'.");

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during robots meta check: " . $e->getMessage())
            ]);
        }
    }
}
