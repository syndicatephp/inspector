<?php

namespace Syndicate\Inspector\Filament\Resources;

use Filament\Resources\Resource;
use Syndicate\Inspector\Filament\Clusters\InspectionCluster;
use Syndicate\Inspector\Filament\Resources\ReportResource\Pages\ListReports;
use Syndicate\Inspector\Models\Report;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static ?string $navigationLabel = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $cluster = InspectionCluster::class;

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
        ];
    }
}
