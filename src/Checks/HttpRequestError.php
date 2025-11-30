<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\Contracts\Check;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\DTOs\InspectionContext;

class HttpRequestError implements Check
{
    public function apply(InspectionContext $context): CheckResult
    {
        return new CheckResult($this, collect());
    }

    public function getChecklist(): string
    {
        return 'Error';
    }

    public function getConfig(): array
    {
        return [];
    }

    public function getName(): string
    {
        return 'HTTP Request Error';
    }
}
