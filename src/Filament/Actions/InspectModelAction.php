<?php

namespace Syndicate\Inspector\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;
use Syndicate\Inspector\Services\InspectorService;

class InspectModelAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Inspect')
            ->icon('heroicon-o-command-line')
            ->color('warning')
            ->requiresConfirmation()
            ->iconPosition(IconPosition::After)
            ->visible(function (Model $record): bool {
                if (!method_exists($record, 'inspection')) {
                    return false;
                }

                return $record->inspection()->shouldInspect();
            })
            ->action(function (Model $record): void {
                resolve(InspectorService::class)->inspectModel($record);

                Notification::make()
                    ->success()
                    ->title('Inspection Completed')
                    ->send();
            });
    }

    public static function make(?string $name = 'inspect_model'): static
    {
        return parent::make($name);
    }
}
