<?php

namespace Syndicate\Inspector\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class ChecklistColumn extends TextColumn
{
    public static function make(string $name = 'checklist'): static
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
                return str(class_basename($record->checklist))->before('Checklist')->toString();
            })
            ->color('info')
            ->sortable();
    }
}
