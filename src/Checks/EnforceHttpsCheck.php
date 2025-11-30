<?php

namespace Syndicate\Inspector\Checks;

use DOMElement;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class EnforceHttpsCheck extends BaseCheck
{
    protected string $checklist = 'Baseline';
    // --- Configuration Properties ---
    protected RemarkLevel $pageInsecureLevel = RemarkLevel::ERROR;
    protected RemarkLevel $linkInsecureLevel = RemarkLevel::ERROR;

    // -----------------------------

    /**
     * Ensures the page itself and all navigational links are served over HTTPS.
     */
    protected function applyCheck(): CheckResult
    {
        $findings = [];

        // 1. Check the primary URL of the page being scanned.
        if (!str_starts_with($this->context->inspection->url(), 'https://')) {
            $findings[] = $this->finding(
                $this->pageInsecureLevel,
                "The page itself is not served over a secure HTTPS connection.",
                ['issue_type' => 'page_insecure', 'page_url' => $this->context->inspection->url()]
            );
        }

        try {
            $crawler = $this->context->crawler();
            $links = $crawler->filter('a[href]');

            // 2. Check all navigational links on the page.
            /** @var DOMElement $linkNode */
            foreach ($links as $linkNode) {
                $href = $linkNode->getAttribute('href');

                if (str_starts_with($href, 'http://')) {
                    $findings[] = $this->finding(
                        $this->linkInsecureLevel,
                        'Navigational link points to an insecure HTTP URL.',
                        ['issue_type' => 'link_insecure', 'link_href' => $href]
                    );
                }
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during HTTPS enforcement check: " . $e->getMessage())
            ]);
        }

        return $findings ? $this->result($findings) : $this->success('The page and all its navigational links use secure HTTPS.');
    }
}
