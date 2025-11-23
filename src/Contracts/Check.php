<?php

namespace Syndicate\Inspector\Contracts;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\DTOs\InspectionContext;

interface Check
{
    public function apply(InspectionContext $context): CheckResult;

    public static function checklist(): string;
}
