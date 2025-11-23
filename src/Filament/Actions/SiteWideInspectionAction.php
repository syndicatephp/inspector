<?php

namespace Syndicate\Inspector\Filament\Actions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use Syndicate\Inspector\Filament\InspectorPlugin;
use Syndicate\Inspector\Jobs\BulkModelInspectionJob;

class SiteWideInspectionAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Global Inspection')
            ->icon('heroicon-o-globe-alt')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Site-Wide Inspection')
            ->modalDescription('Are you sure you want to run inspection on ALL registered model types? This will trigger a comprehensive inspection across your entire site.')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->iconPosition(IconPosition::After)
            ->action(function (): void {
                $inspectableTypes = $this->getInspectableTypes();

                foreach ($inspectableTypes as $modelClass) {
                    BulkModelInspectionJob::dispatch($modelClass, Auth::user());
                }

                Notification::make()
                    ->title('Site-Wide Inspection Started')
                    ->body(function () use ($inspectableTypes) {
                        return 'Dispatched inspection jobs for '.count($inspectableTypes).' model types.';
                    })
                    ->success()
                    ->send();
            });
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

    public static function make(?string $name = 'inspect_site'): static
    {
        return parent::make($name);
    }
}
