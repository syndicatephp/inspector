<?php

namespace Syndicate\Inspector\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class CheckColumn extends TextColumn
{
    public static function make(string $name = 'check'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->searchable()
            ->badge()
            ->formatStateUsing(function ($record): string {
                return str(class_basename($record->check))->before('Check')->headline()->toString();
            })
            ->color('info')
            ->sortable();
    }
}
