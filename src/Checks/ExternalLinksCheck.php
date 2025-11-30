<?php

namespace Syndicate\Inspector\Checks;

use DOMElement;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Syndicate\Inspector\Contracts\Checks\DeterminesExternalLinks;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class ExternalLinksCheck extends BaseCheck
{
    protected string $checklist = 'Content';
    // --- Configuration Properties ---
    protected RemarkLevel $level = RemarkLevel::WARNING;
    protected int $timeout = 5;

    // -----------------------------

    /**
     * Checks all external links on the page for 4xx or 5xx status codes.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $linkDeterminer = app(DeterminesExternalLinks::class);

            $externalUrls = $this->collectExternalUrls($linkDeterminer);

            if (empty($externalUrls)) {
                return $this->success('No external links found to check.');
            }

            $responses = Http::pool(fn(Pool $pool) => collect($externalUrls)
                ->each(
                    fn($url) => $pool
                        ->as($url)
                        ->timeout($this->timeout)
                        ->head($url)
                ));

            $findings = [];
            foreach ($responses as $requestedUrl => $responseOrException) {
                // Case 1: A ConnectionException occurred.
                if ($responseOrException instanceof ConnectionException) {
                    $findings[] = $this->finding(
                        $this->level,
                        "Could not connect to the external link.",
                        [
                            'issue_type' => 'connection_error',
                            'link_url' => $requestedUrl,
                            'error_message' => $responseOrException->getMessage(),
                        ]
                    );
                    continue;
                }

                /** @var HttpResponse $response */
                $response = $responseOrException;

                // Case 2: A response was received, but it indicates failure (4xx or 5xx).
                if ($response->failed()) {
                    $findings[] = $this->finding(
                        $this->level,
                        "External link is broken or inaccessible. Responded with status code: " . $response->status(),
                        [
                            'issue_type' => 'broken_link',
                            'link_url' => $requestedUrl,
                            'status_code' => $response->status(),
                        ]
                    );
                }
            }

            return $findings ? $this->result($findings) : $this->success(
                'All external links are accessible.',
                ['external_urls' => $externalUrls]
            );

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during external link check: " . $e->getMessage())
            ]);
        }
    }

    /**
     * Collects and de-duplicates all unique external URLs from the page.
     * The logic for determining "external" is now delegated to the provider.
     */
    private function collectExternalUrls(DeterminesExternalLinks $linkDeterminer): array
    {
        $externalUrls = [];
        $links = $this->context->crawler()->filter('a[href]');

        /** @var DOMElement $linkNode */
        foreach ($links as $linkNode) {
            $href = $linkNode->getAttribute('href');

            // Basic filtering for irrelevant href schemes
            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href,
                    'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            if ($linkDeterminer->isExternal($href, $this->context)) {
                $externalUrls[$href] = true;
            }
        }

        return array_keys($externalUrls);
    }
}
