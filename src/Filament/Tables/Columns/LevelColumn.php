<?php

namespace Syndicate\Inspector\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class LevelColumn extends TextColumn
{
    public static function make(string $name = 'level'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sortable(query: fn($query, string $direction) => $query->orderBy('level_severity', $direction));
        $this->badge();
    }
}
