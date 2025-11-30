<?php

namespace Syndicate\Inspector\Helpers;

use Syndicate\Inspector\Contracts\Checks\DeterminesExternalLinks;
use Syndicate\Inspector\DTOs\InspectionContext;

class DefaultExternalLinkDeterminer implements DeterminesExternalLinks
{
    public function isExternal(string $href, InspectionContext $context): bool
    {
        $currentHost = parse_url($context->inspection->url(), PHP_URL_HOST);

        if (empty($href) || empty($currentHost)) {
            return false;
        }

        $linkHost = parse_url($href, PHP_URL_HOST);

        // An external link MUST have a host, and that host MUST NOT be the same as the current page's host.
        return $linkHost !== null && $linkHost !== $currentHost;
    }
}
