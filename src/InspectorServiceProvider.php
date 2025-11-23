<?php

namespace Syndicate\Inspector;

use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Syndicate\Inspector\Contracts\Checks\DeterminesExternalLinks;
use Syndicate\Inspector\Contracts\Checks\DeterminesInternalLinks;
use Syndicate\Inspector\Events\BulkInspectionCompleted;
use Syndicate\Inspector\Helpers\DefaultExternalLinkDeterminer;
use Syndicate\Inspector\Helpers\DefaultInternalLinkDeterminer;
use Syndicate\Inspector\Listeners\NotifyOfBulkInspectionCompleted;

class InspectorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('inspector')
            ->hasMigrations('create_inspection_reports_table', 'create_inspection_remarks_table');
    }

    public function bootingPackage()
    {
        Event::listen(
            BulkInspectionCompleted::class,
            NotifyOfBulkInspectionCompleted::class,
        );

        $this->app->singletonIf(
            DeterminesInternalLinks::class,
            DefaultInternalLinkDeterminer::class
        );

        $this->app->singletonIf(
            DeterminesExternalLinks::class,
            DefaultExternalLinkDeterminer::class
        );
    }
}
