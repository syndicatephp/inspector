<?php

namespace Syndicate\Inspector\Filament\Tables\Actions;

use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Syndicate\Inspector\Models\Remark;
use Syndicate\Inspector\Models\Report;
use Syndicate\Inspector\Services\InspectorService;

class InspectRecordAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Inspect')
            ->icon('heroicon-o-command-line')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Run Inspection')
            ->modalDescription('This will run a fresh inspection on the related model to check for current issues.')
            ->modalIcon('heroicon-o-command-line')
            ->iconPosition(IconPosition::After)
            ->action(function (Model $record): void {
                /** @var Remark|Report $record */
                if ($record->inspectable_type === null) {
                    resolve(InspectorService::class)->inspectUrl($record->url);
                } else {
                    resolve(InspectorService::class)->inspectModel($record->inspectable);
                }

                Notification::make()
                    ->title('Inspection Completed')
                    ->success()
                    ->send();
            });
    }

    public static function make(?string $name = 'inspect_record'): static
    {
        return parent::make($name);
    }
}
