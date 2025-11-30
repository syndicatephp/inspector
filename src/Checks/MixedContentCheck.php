<?php

namespace Syndicate\Inspector\Checks;

use Symfony\Component\DomCrawler\Crawler;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class MixedContentCheck extends BaseCheck
{
    protected string $checklist = 'Baseline';
    // --- Configuration Properties ---
    protected RemarkLevel $level = RemarkLevel::ERROR;

    // -----------------------------

    /**
     * Scans for insecurely loaded assets on a secure page.
     */
    protected function applyCheck(): CheckResult
    {
        if (str_starts_with($this->context->inspection->url(), 'http://')) {
            return $this->success('Skipped: Page is not loaded over HTTPS.');
        }

        $findings = [];

        try {
            $crawler = $this->context->crawler();

            $assetSelectors = [
                'img[src]' => 'src',
                'script[src]' => 'src',
                'link[rel="stylesheet"][href]' => 'href',
                'video[src]' => 'src',
                'audio[src]' => 'src',
                'source[src]' => 'src',
                'iframe[src]' => 'src',
            ];

            foreach ($assetSelectors as $selector => $attribute) {
                $crawler->filter($selector)->each(
                    function (Crawler $node) use (&$findings, $attribute) {
                        $assetUrl = $node->attr($attribute);

                        // Check if the asset URL starts with "http://" (but not "//", which is protocol-relative).
                        if (str_starts_with($assetUrl, 'http://')) {
                            $findings[] = $this->finding(
                                $this->level,
                                "Insecure asset loaded on a secure page (mixed content).",
                                [
                                    'issue_type' => 'mixed_content',
                                    'tag' => $node->nodeName(),
                                    'asset_url' => $assetUrl,
                                ]
                            );
                        }
                    }
                );
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during mixed content check: " . $e->getMessage())
            ]);
        }

        return $findings ? $this->result($findings) : $this->success('No mixed content found on the page.');
    }
}
