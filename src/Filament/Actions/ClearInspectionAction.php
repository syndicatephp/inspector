<?php

namespace Syndicate\Inspector\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;
use Syndicate\Inspector\Models\Report;

class ClearInspectionAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Clear')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Clear Inspection')
            ->modalDescription('Are you sure you want to clear the inspection for this model?')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->iconPosition(IconPosition::After)
            ->action(function (Model $record): void {
                Report::query()
                    ->where('inspectable_type', $record->getMorphClass())
                    ->where('inspectable_id', $record->getKey())
                    ->delete();

                Notification::make()
                    ->title('Inspection Cleared')
                    ->success()
                    ->send();
            });
    }

    public static function make(?string $name = 'clear_inspection'): static
    {
        return parent::make($name);
    }
}
