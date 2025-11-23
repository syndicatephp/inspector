<?php

namespace Syndicate\Inspector\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class MessageColumn extends TextColumn
{
    public static function make(string $name = 'message'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->searchable()
            ->limit(80)
            ->wrap();
    }
}
