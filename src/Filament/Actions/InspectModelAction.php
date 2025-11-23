<?php

namespace Syndicate\Inspector\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Syndicate\Inspector\Contracts\Inspectable;

class InspectModelAction extends Action
{
    public static function make(?string $name = 'inspect_model'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Inspect')
            ->icon('heroicon-o-command-line')
            ->color('warning')
            ->requiresConfirmation()
            ->iconPosition(IconPosition::After)
            ->visible(function (Model&Inspectable $record): bool {
                return $record->shouldBeInspected();
            })
            ->action(function (Model&Inspectable $record): void {
                $record->runChecksNow(Auth::user());
            });
    }
}
