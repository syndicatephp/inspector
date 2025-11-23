<?php

namespace Syndicate\Inspector\Contracts\Checks;

use Syndicate\Inspector\DTOs\InspectionContext;

/**
 * A contract for a class that can efficiently validate if an internal
 * URL path exists within the application, without making an HTTP request.
 */
interface ValidatesInternalLinks
{
    /**
     * Checks if the given path is a valid, routable path in the application.
     * @param  string  $path
     * @param  CheckContext  $context
     * @return bool True if the path is valid, false otherwise.
     */
    public function isValid(string $path, InspectionContext $context): bool;
}
