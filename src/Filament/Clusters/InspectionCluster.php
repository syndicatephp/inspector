<?php

namespace Syndicate\Inspector\Filament\Clusters;

use Filament\Clusters\Cluster;

class InspectionCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $slug = 'inspections';
    protected static ?string $navigationLabel = 'Inspections';
    protected static ?string $clusterBreadcrumb = 'Inspections';
}
