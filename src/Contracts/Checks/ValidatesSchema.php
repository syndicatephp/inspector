<?php

namespace Syndicate\Inspector\Contracts\Checks;

use Syndicate\Inspector\DTOs\InspectionContext;

interface ValidatesSchema
{
    /**
     * @param  string  $jsonLdContent
     * @param  InspectionContext  $context
     * @return Finding[] An array of validation errors, or an empty array if valid.
     */
    public function getValidationErrors(string $jsonLdContent, InspectionContext $context): array;
}
