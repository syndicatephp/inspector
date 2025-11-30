<?php

namespace Syndicate\Inspector\Filament\Resources;

use Filament\Resources\Resource;
use Syndicate\Inspector\Filament\Clusters\InspectionCluster;
use Syndicate\Inspector\Filament\Resources\RemarkResource\Pages\ListRemarks;
use Syndicate\Inspector\Models\Remark;

class RemarkResource extends Resource
{
    protected static ?string $model = Remark::class;
    protected static ?string $navigationLabel = 'Remarks';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $cluster = InspectionCluster::class;

    public static function getPages(): array
    {
        return [
            'index' => ListRemarks::route('/'),
        ];
    }
}
