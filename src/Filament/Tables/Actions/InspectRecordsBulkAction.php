<?php

namespace Syndicate\Inspector\Filament\Tables\Actions;

use Exception;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Syndicate\Inspector\Contracts\Inspectable;
use Syndicate\Inspector\Models\Remark;
use Syndicate\Inspector\Models\Report;

class InspectRecordsBulkAction extends BulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Inspect Selected')
            ->icon('heroicon-o-command-line')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Queue Bulk Inspection')
            ->modalDescription('This will queue fresh inspections for all related models for the selected records. Inspections will run in the background.')
            ->modalIcon('heroicon-o-command-line')
            ->iconPosition(IconPosition::After)
            ->action(function (Collection $records): void {
                $successCount = 0;
                $errorCount = 0;
                $skippedCount = 0;
                $processedModels = [];

                foreach ($records as $record) {
                    /** @var Remark|Report $record */
                    if (!($record instanceof Remark || $record instanceof Report)) {
                        $skippedCount++;
                        continue;
                    }

                    $inspectable = $record->inspectable;

                    if (!$inspectable instanceof Inspectable) {
                        $skippedCount++;
                        continue;
                    }

                    if (!$inspectable->shouldBeInspected()) {
                        $skippedCount++;
                        continue;
                    }

                    // Avoid inspecting the same model multiple times
                    $modelKey = get_class($inspectable).':'.$inspectable->getKey();
                    if (in_array($modelKey, $processedModels)) {
                        continue;
                    }
                    $processedModels[] = $modelKey;

                    try {
                        $inspectable->queueChecks(Auth::user());
                        $successCount++;
                    } catch (Exception $e) {
                        $errorCount++;
                    }
                }

                // Build notification message
                $messages = [];
                if ($successCount > 0) {
                    $messages[] = "{$successCount} model(s) queued for inspection";
                }
                if ($errorCount > 0) {
                    $messages[] = "{$errorCount} inspection(s) failed to queue";
                }
                if ($skippedCount > 0) {
                    $messages[] = "{$skippedCount} record(s) skipped (invalid or not inspectable)";
                }

                $title = $errorCount > 0 ? 'Bulk Inspection Queued with Errors' : 'Bulk Inspection Queued';
                $color = $errorCount > 0 ? 'warning' : 'success';

                Notification::make()
                    ->title($title)
                    ->body(implode('. ', $messages).'.')
                    ->color($color)
                    ->duration(8000)
                    ->send();
            });
    }

    public static function make(?string $name = 'inspect_records'): static
    {
        return parent::make($name);
    }
}
