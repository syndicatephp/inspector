<?php

namespace Syndicate\Inspector\Filament\Tables\Filters;

use Filament\Facades\Filament;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Relations\Relation;
use Syndicate\Inspector\Filament\InspectorPlugin;

class InspectableFilter extends SelectFilter
{
    public static function make(?string $name = 'inspectable_type'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options($this->getInspectableTypesArr())
            ->label('Inspectable');
    }

    protected function getInspectableTypesArr(): array
    {
        $currentPanel = Filament::getCurrentPanel();
        $plugin = null;

        if ($currentPanel && $currentPanel->hasPlugin('syndicate-inspector')) {
            $plugin = $currentPanel->getPlugin('syndicate-inspector');
        }

        /** @var ?InspectorPlugin $plugin */
        $checkableTypes = $plugin?->getInspectableTypes() ?? [];

        if (empty($checkableTypes)) {
            return [];
        }

        return collect($checkableTypes)->mapWithKeys(function ($value, $key) {
            return [Relation::getMorphAlias($value) => class_basename($value)];
        })->toArray();
    }
}
