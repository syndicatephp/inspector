<?php

namespace Syndicate\Inspector\Filament\Clusters;

use Filament\Clusters\Cluster;

class InspectionCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $slug = 'inspection';
    protected static ?string $navigationLabel = 'Inspection';
    protected static ?string $clusterBreadcrumb = 'Inspection';
}
