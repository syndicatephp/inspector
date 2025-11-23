<?php

namespace Syndicate\Inspector\Filament\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;
use Syndicate\Inspector\Enums\RemarkLevel;

class LevelFilter extends SelectFilter
{
    public static function make(?string $name = 'level'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->options(RemarkLevel::class);
    }
}


