<?php

namespace Syndicate\Inspector\Contracts\Checks;

use Syndicate\Inspector\DTOs\InspectionContext;

interface DeterminesInternalLinks
{
    /**
     * Determines if a given href is considered an internal link.
     * @param  string  $href  The full href attribute to check.
     * @param  InspectionContext  $context  The context of the current check run.
     * @return bool True if the link is internal, false otherwise.
     */
    public function isInternal(string $href, InspectionContext $context): bool;
}
