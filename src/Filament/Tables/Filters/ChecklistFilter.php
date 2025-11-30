<?php

namespace Syndicate\Inspector\Filament\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChecklistFilter extends SelectFilter
{
    public static function make(?string $name = 'checklist'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options(function (Table $table): array {
                return $table->getQuery()->distinct()->pluck('checklist')->toArray();
            });
    }
}


