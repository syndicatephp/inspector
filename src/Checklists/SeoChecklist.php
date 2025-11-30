<?php

namespace Syndicate\Inspector\Checklists;

use Syndicate\Inspector\Checks\CanonicalUrlCheck;
use Syndicate\Inspector\Checks\HreflangCheck;
use Syndicate\Inspector\Checks\MetaDescriptionCheck;
use Syndicate\Inspector\Checks\OpenGraphCheck;
use Syndicate\Inspector\Checks\RobotsMetaCheck;
use Syndicate\Inspector\Checks\SchemaCheck;
use Syndicate\Inspector\Checks\TwitterCardCheck;
use Syndicate\Inspector\Contracts\Check;

class SeoChecklist
{
    /**
     * @return Check[]
     */
    public static function checks(): array
    {
        return [
            SchemaCheck::make(),
            TwitterCardCheck::make(),
            RobotsMetaCheck::make(),
            OpenGraphCheck::make(),
            MetaDescriptionCheck::make(),
            HreflangCheck::make(),
            CanonicalUrlCheck::make(),
        ];
    }
}
