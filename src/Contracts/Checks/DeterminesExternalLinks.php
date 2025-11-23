<?php

namespace Syndicate\Inspector\Contracts\Checks;

use Syndicate\Inspector\DTOs\InspectionContext;

interface DeterminesExternalLinks
{
    /**
     * Determines if a given href is considered an external link.
     * @param  string  $href  The full href attribute to check.
     * @param  InspectionContext  $context  The context of the current check run.
     * @return bool True if the link is external, false otherwise.
     */
    public function isExternal(string $href, InspectionContext $context): bool;
}
