<?php

namespace Syndicate\Inspector\Checks;

use DOMElement;
use Syndicate\Inspector\Contracts\Checks\DeterminesInternalLinks;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class LinkConsistencyCheck extends BaseCheck
{
    protected string $checklist = 'Baseline';
    // --- Configuration Properties ---
    protected RemarkLevel $level = RemarkLevel::WARNING;
    /** @var string[] File extensions to ignore when checking for trailing slashes. */
    protected array $ignoredExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico',
        'zip', 'rar', 'xml', 'rss', 'atom', 'json', 'js', 'css',
    ];

    // -----------------------------

    /**
     * Checks for consistent use of trailing slashes on internal links.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $linkDeterminer = app(DeterminesInternalLinks::class);

            $crawler = $this->context->crawler();
            $links = $crawler->filter('a[href]');

            $expectedFormat = null;
            $firstLinkExample = null;

            /** @var DOMElement $linkNode */
            foreach ($links as $linkNode) {
                $href = $linkNode->getAttribute('href');

                if (!$this->isRelevantLink($href) || !$linkDeterminer->isInternal($href, $this->context)) {
                    continue;
                }

                $path = strtok($href, '?#');
                $hasTrailingSlash = str_ends_with($path, '/') && $path !== '/';

                if ($expectedFormat === null) {
                    $expectedFormat = $hasTrailingSlash;
                    $firstLinkExample = $href;
                    continue;
                }

                if ($hasTrailingSlash !== $expectedFormat) {
                    $expected = $expectedFormat ? 'include' : 'not include';
                    $violating = $hasTrailingSlash ? 'includes' : 'does not include';
                    $message = "Link format is inconsistent. The first link ('$firstLinkExample') set the expectation to $expected a trailing slash, but '$href' $violating one.";

                    return $this->result([
                        $this->finding(
                            $this->level,
                            $message,
                            [
                                'issue_type' => 'inconsistent_trailing_slash',
                                'expected_format' => $expectedFormat ? 'with_slash' : 'without_slash',
                                'violating_link' => $href,
                                'rule_set_by_link' => $firstLinkExample,
                            ]
                        )
                    ]);
                }
            }
        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during link consistency check: " . $e->getMessage())
            ]);
        }

        return $this->success('Internal links use a consistent trailing slash format.');
    }

    /**
     * Performs basic filtering on hrefs that are never relevant.
     */
    private function isRelevantLink(?string $href): bool
    {
        if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href,
                'tel:') || str_starts_with($href, 'javascript:')) {
            return false;
        }

        $path = strtok($href, '?#');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return !in_array($extension, $this->ignoredExtensions, true);
    }
}
