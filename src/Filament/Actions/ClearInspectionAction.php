<?php

namespace Syndicate\Inspector\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;
use Syndicate\Inspector\Contracts\Inspectable;
use Syndicate\Inspector\Models\Remark;
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
            ->modalDescription('Are you sure you want to clear all inspection data for this model? This will delete all inspection reports and remarks.')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->iconPosition(IconPosition::After)
            ->action(function (Model $record): void {
                Remark::query()
                    ->where('inspectable_type', $record->getMorphClass())
                    ->where('inspectable_id', $record->getKey())
                    ->delete();

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
