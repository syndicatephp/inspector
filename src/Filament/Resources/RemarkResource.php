<?php

namespace Syndicate\Inspector\Filament\Resources;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Syndicate\Inspector\Filament\InspectorPlugin;
use Syndicate\Inspector\Filament\Resources\RemarkResource\Pages\ListRemarks;
use Syndicate\Inspector\Models\Remark;

class RemarkResource extends Resource
{
    protected static ?string $model = Remark::class;
    protected static ?string $navigationLabel = 'Remarks';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getPages(): array
    {
        return [
            'index' => ListRemarks::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $currentPanel = Filament::getCurrentPanel();
        $plugin = null;

        if ($currentPanel && $currentPanel->hasPlugin('syndicate-inspector')) {
            $plugin = $currentPanel->getPlugin('syndicate-inspector');
        }

        /** @var ?InspectorPlugin $plugin */
        $inspectableTypes = $plugin?->getInspectableTypes() ?? [];

        if (empty($inspectableTypes)) {
            return parent::getEloquentQuery();
        }

        $aliases = collect($inspectableTypes)->map(function ($value) {
            return Relation::getMorphAlias($value);
        })->toArray();

        return parent::getEloquentQuery()->whereIn('inspectable_type', $aliases);
    }
}
