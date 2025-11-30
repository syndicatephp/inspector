<?php

namespace Syndicate\Inspector\Filament\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CheckFilter extends SelectFilter
{
    public static function make(?string $name = 'check'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options(function (Table $table): array {
                return $table->getQuery()->distinct()->pluck('check')->mapWithKeys(fn($check) => [$check => ucfirst($check)])->toArray();
            });
    }
}
