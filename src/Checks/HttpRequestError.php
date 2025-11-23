<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\Contracts\Check;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\DTOs\InspectionContext;

class HttpRequestError implements Check
{
    public static function checklist(): string
    {
        return 'Error';
    }

    public function apply(InspectionContext $context): CheckResult
    {
        return new CheckResult(collect());
    }
}
