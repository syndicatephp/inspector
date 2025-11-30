<?php

namespace Syndicate\Inspector\Checklists;

use Syndicate\Inspector\Checks\EnforceHttpsCheck;
use Syndicate\Inspector\Checks\LinkConsistencyCheck;
use Syndicate\Inspector\Checks\MixedContentCheck;
use Syndicate\Inspector\Checks\StatusCodeCheck;
use Syndicate\Inspector\Checks\TitleCheck;
use Syndicate\Inspector\Checks\ViewportCheck;
use Syndicate\Inspector\Contracts\Check;

class BaselineChecklist
{
    /**
     * @return Check[]
     */
    public static function checks(): array
    {
        return [
            EnforceHttpsCheck::make(),
            LinkConsistencyCheck::make(),
            TitleCheck::make(),
            ViewportCheck::make(),
            StatusCodeCheck::make(),
            MixedContentCheck::make(),
        ];
    }
}
