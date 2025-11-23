<?php

namespace Syndicate\Inspector\Filament\Resources;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Syndicate\Inspector\Filament\Resources\ReportResource\Pages\ListReports;
use Syndicate\Inspector\Models\Report;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static ?string $navigationLabel = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $currentPanel = Filament::getCurrentPanel();
        $plugin = null;

        if ($currentPanel && $currentPanel->hasPlugin('syndicate-inspector')) {
            $plugin = $currentPanel->getPlugin('syndicate-inspector');
        }

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
