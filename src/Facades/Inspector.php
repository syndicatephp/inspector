<?php

namespace Syndicate\Inspector\Facades;

use Illuminate\Support\Facades\Facade;
use Syndicate\Inspector\Services\InspectorService;

/**
 * @see InspectorService
 */
class Inspector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return InspectorService::class;
    }
}
