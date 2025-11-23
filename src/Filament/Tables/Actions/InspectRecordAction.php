<?php

namespace Syndicate\Inspector\Filament\Tables\Actions;

use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Syndicate\Inspector\Contracts\Inspectable;
use Syndicate\Inspector\Models\Remark;
use Syndicate\Inspector\Models\Report;

class InspectRecordAction extends Action
{
    public static function make(?string $name = 'inspect_record'): static
    {
        return parent::make($name);
    }

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
            ->visible(function (Model $record): bool {
                // Check if this is a Remark or Report with an inspectable relationship
                if (!($record instanceof Remark || $record instanceof Report)) {
                    return false;
                }

                $inspectable = $record->inspectable;
                return $inspectable instanceof Inspectable && $inspectable->shouldBeInspected();
            })
            ->action(function (Model $record): void {
                /** @var Remark|Report $record */
                $inspectable = $record->inspectable;

                if (!$inspectable instanceof Inspectable) {
                    Notification::make()
                        ->title('Inspection Failed')
                        ->body('Could not access the related model for inspection.')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    $inspectable->runChecksNow(Auth::user());

                    $modelLabel = class_basename($inspectable);

                    Notification::make()
                        ->title('Inspection Completed')
                        ->body("Successfully ran inspection on {$modelLabel} model.")
                        ->success()
                        ->duration(5000)
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Inspection Failed')
                        ->body("Failed to run inspection: {$e->getMessage()}")
                        ->danger()
                        ->send();
                }
            });
    }
}
