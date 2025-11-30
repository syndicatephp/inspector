<?php

namespace Syndicate\Inspector\Checklists;

use Syndicate\Inspector\Checks\ExternalLinksCheck;
use Syndicate\Inspector\Checks\H1Check;
use Syndicate\Inspector\Checks\HeadingHierarchyCheck;
use Syndicate\Inspector\Checks\ImageIntegrityCheck;
use Syndicate\Inspector\Checks\InternalLinksCheck;
use Syndicate\Inspector\Contracts\Check;

class ContentChecklist
{
    /**
     * @return Check[]
     */
    public static function checks(): array
    {
        return [
            ImageIntegrityCheck::make(),
            H1Check::make(),
            HeadingHierarchyCheck::make(),
            InternalLinksCheck::make(),
            ExternalLinksCheck::make(),
        ];
    }
}
