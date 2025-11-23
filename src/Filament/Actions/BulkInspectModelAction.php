<?php

namespace Syndicate\Inspector\Filament\Actions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use Syndicate\Inspector\Enums\RemarkLevel;
use Syndicate\Inspector\Filament\InspectorPlugin;
use Syndicate\Inspector\Jobs\BulkModelInspectionJob;
use Syndicate\Inspector\Jobs\SimpleBulkModelInspectionJob;

class BulkInspectModelAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Inspect')
            ->icon('heroicon-o-rocket-launch')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Run Inspection')
            ->modalDescription('Configure and run inspections. Leave Model Type empty for site-wide inspection across all registered types, or select a specific type for targeted inspection.')
            ->modalIcon('heroicon-o-rocket-launch')
            ->iconPosition(IconPosition::After)
            ->form([
                Select::make('inspectable_type')
                    ->label('Model Type (Optional)')
                    ->placeholder('Leave empty for site-wide inspection')
                    ->nullable()
                    ->options($this->getDispatchModelArr())
                    ->helperText('Leave empty to inspect ALL registered model types (site-wide), or select a specific type for targeted inspection')
                    ->searchable()
                    ->native(false),
                Select::make('level')
                    ->label('Filter by Level (Optional)')
                    ->placeholder('All levels (recommended)')
                    ->nullable()
                    ->options(RemarkLevel::class)
                    ->helperText('Leave empty to inspect all models, or select a specific level to only inspect models with existing issues at that severity')
                    ->searchable()
                    ->native(false),
            ])
            ->action(function ($data) {
                $level = isset($data['level']) ? RemarkLevel::from($data['level']) : null;
                $inspectableType = $data['inspectable_type'] ?? null;

                // Site-wide inspection when no specific type is selected
                if (empty($inspectableType)) {
                    $inspectableTypes = $this->getInspectableTypes();

                    if (empty($inspectableTypes)) {
                        Notification::make()
                            ->title('No Models Configured')
                            ->body('No inspectable model types are registered in the Inspector plugin. Please configure your models first.')
                            ->warning()
                            ->duration(8000)
                            ->send();
                        return;
                    }

                    // For site-wide inspection, level filtering uses SimpleBulkModelInspectionJob for each type
                    if ($level) {
                        foreach ($inspectableTypes as $modelClass) {
                            SimpleBulkModelInspectionJob::dispatch($modelClass, $level, Auth::user());
                        }

                        Notification::make()
                            ->title('Site-Wide Filtered Inspection Started')
                            ->body("Inspecting all " . count($inspectableTypes) . " model types with {$level->getLabel()} level issues. This will re-check only models that already have issues at this severity.")
                            ->success()
                            ->duration(8000)
                            ->send();
                    } else {
                        foreach ($inspectableTypes as $modelClass) {
                            BulkModelInspectionJob::dispatch($modelClass, Auth::user());
                        }

                        Notification::make()
                            ->title('Site-Wide Inspection Started')
                            ->body("Comprehensive inspection started for all " . count($inspectableTypes) . " model types. This includes cleanup of stale data and will send notifications when complete.")
                            ->success()
                            ->duration(8000)
                            ->send();
                    }

                    return;
                }

                // Single-type inspection when specific type is selected
                $modelLabel = class_basename($inspectableType);

                if ($level) {
                    // Use simple job when level filtering is specified
                    SimpleBulkModelInspectionJob::dispatch($inspectableType, $level, Auth::user());

                    Notification::make()
                        ->title('Filtered Inspection Started')
                        ->body("Inspecting {$modelLabel} models with {$level->getLabel()} level issues. This will re-check only models that already have issues at this severity.")
                        ->success()
                        ->duration(5000)
                        ->send();
                } else {
                    // Use comprehensive job when no level filtering is needed
                    BulkModelInspectionJob::dispatch($inspectableType, Auth::user());

                    Notification::make()
                        ->title('Bulk Inspection Started')
                        ->body("Comprehensive inspection started for all {$modelLabel} models. This includes cleanup of stale data and will send a notification when complete.")
                        ->success()
                        ->duration(5000)
                        ->send();
                }
            });
    }

    public static function make(?string $name = 'bulk_inspect'): static
    {
        return parent::make($name);
    }

    protected function getInspectableTypes(): array
    {
        $currentPanel = Filament::getCurrentPanel();
        $plugin = null;

        if ($currentPanel && $currentPanel->hasPlugin('syndicate-inspector')) {
            $plugin = $currentPanel->getPlugin('syndicate-inspector');
        }

        /** @var ?InspectorPlugin $plugin */
        return $plugin?->getInspectableTypes() ?? [];
    }

    protected function getDispatchModelArr(): array
    {
        $checkableTypes = $this->getInspectableTypes();

        if (empty($checkableTypes)) {
            return [];
        }

        return collect($checkableTypes)->mapWithKeys(function ($value, $key) {
            return [$value => class_basename($value)];
        })->toArray();
    }
}
