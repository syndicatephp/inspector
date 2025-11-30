<?php

namespace Syndicate\Inspector\Filament\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InspectableFilter extends SelectFilter
{
    public static function make(?string $name = 'inspectable_type'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Inspectable')
            ->searchable()
            ->options(function (Table $table): array {
                return $table->getQuery()->distinct()->pluck('inspectable_type')->toArray();
            });
    }
}
