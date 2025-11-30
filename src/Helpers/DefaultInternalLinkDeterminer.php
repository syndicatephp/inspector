<?php

namespace Syndicate\Inspector\Helpers;

use Syndicate\Inspector\Contracts\Checks\DeterminesInternalLinks;
use Syndicate\Inspector\DTOs\InspectionContext;

class DefaultInternalLinkDeterminer implements DeterminesInternalLinks
{
    public function isInternal(string $href, InspectionContext $context): bool
    {
        // Get the host of the page being checked.
        $currentHost = parse_url($context->inspection->url(), PHP_URL_HOST);

        if (empty($href) || empty($currentHost)) {
            return false;
        }

        $linkHost = parse_url($href, PHP_URL_HOST);

        // It's internal if it has no host (relative link like /page)
        // or the host matches the current page's host.
        return $linkHost === null || $linkHost === $currentHost;
    }
}
