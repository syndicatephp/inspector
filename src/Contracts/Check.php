<?php

namespace Syndicate\Inspector\Contracts;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\DTOs\InspectionContext;

interface Check
{
    public function getChecklist(): string;

    public function apply(InspectionContext $context): CheckResult;

    public function getConfig(): array;

    public function getName(): string;
}
