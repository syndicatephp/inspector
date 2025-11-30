<?php

namespace Syndicate\Inspector\Filament\Infolists;

use Closure;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Contracts\Support\Htmlable;

class RemarkDetails extends Section
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->columns(2);
        $this->schema([
            TextEntry::make('check')
                ->label('Check Name'),

            TextEntry::make('checklist')
                ->formatStateUsing(function ($state) {
                    return class_basename($state);
                })
                ->label('Checklist Name'),

            TextEntry::make('level')
                ->label('Level')
                ->badge(),

            TextEntry::make('created_at')
                ->label('Detected At')
                ->dateTime(),

            TextEntry::make('message')
                ->label('Message'),

            TextEntry::make('url')
                ->label('URL'),

            ViewEntry::make('details')
                ->label('Detailed Information')
                ->view('inspector::infolists.details')
                ->columnSpanFull(),

            ViewEntry::make('config')
                ->label('Check Configuration')
                ->view('inspector::infolists.config')
                ->columnSpanFull(),
        ]);
    }

    public static function make(Htmlable|array|Closure|string|null $heading = 'Remark Details'): static
    {
        return parent::make($heading);
    }
}
