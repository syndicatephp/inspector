<?php

namespace Syndicate\Inspector\Checks;

use DOMElement;
use Syndicate\Inspector\Contracts\Checks\DeterminesInternalLinks;
use Syndicate\Inspector\Contracts\Checks\ValidatesInternalLinks;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class InternalLinksCheck extends BaseCheck
{
    // --- Configuration Properties ---
    protected RemarkLevel $level = RemarkLevel::ERROR;

    // -----------------------------

    public static function checklist(): string
    {
        return 'Content';
    }

    /**
     * Checks all internal links on the page for 4xx or 5xx status codes.
     */
    protected function applyCheck(): CheckResult
    {
        if (!app()->bound(ValidatesInternalLinks::class)) {
            return $this->result([
                $this->finding(
                    RemarkLevel::INFO,
                    'Skipped: To enable this check, register an implementation of the "ValidatesInternalLinks" contract in a service provider.'
                )
            ]);
        }

        try {
            $linkValidator = resolve(ValidatesInternalLinks::class);
            $linkDeterminer = resolve(DeterminesInternalLinks::class);
            $findings = [];
            $checkedPaths = [];

            $links = $this->context->crawler()->filter('a[href]');

            /** @var DOMElement $linkNode */
            foreach ($links as $linkNode) {
                $href = $linkNode->getAttribute('href');

                if ($this->isRelevantLink($href) && $linkDeterminer->isInternal($href, $this->context)) {
                    $path = strtok($href, '?#');

                    if (isset($checkedPaths[$path])) {
                        continue;
                    }

                    if (!$linkValidator->isValid($path, $this->context)) {
                        $findings[] = $this->finding(
                            $this->level,
                            "Internal link appears to be broken. Path '$path' is not routable.",
                            ['issue_type' => 'unroutable_link', 'link_path' => $path]
                        );
                    }

                    $checkedPaths[$path] = true;
                }
            }

            return $findings ? $this->result($findings) : $this->success('All internal links appear to be valid.');

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during internal link check: " . $e->getMessage())
            ]);
        }
    }

    /**
     * Performs basic filtering on hrefs that are never relevant.
     */
    private function isRelevantLink(?string $href): bool
    {
        return !empty($href) && !str_starts_with($href, '#') && !str_starts_with($href,
                'mailto:') && !str_starts_with($href, 'tel:');
    }
}
