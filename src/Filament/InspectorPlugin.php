<?php

namespace Syndicate\Inspector\Filament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Syndicate\Inspector\Filament\Resources\RemarkResource;
use Syndicate\Inspector\Filament\Resources\ReportResource;

class InspectorPlugin implements Plugin
{
    protected string $reportResource = ReportResource::class;
    protected string $remarkResource = RemarkResource::class;
    /**
     * @var array<int, class-string>
     */
    protected array $inspectableTypes = [];
    protected ?Closure $recordTitleMapping = null;
    protected ?Closure $recordUrlMapping = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function remarkResource(string $resourceClass): static
    {
        $this->remarkResource = $resourceClass;
        return $this;
    }

    public function reportResource(string $resourceClass): static
    {
        $this->reportResource = $resourceClass;
        return $this;
    }

    public function inspectableTypes(array $inspectableTypes): static
    {
        $this->inspectableTypes = $inspectableTypes;
        return $this;
    }

    public function getId(): string
    {
        return 'syndicate-inspector';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->widgets([
                Widgets\RemarkLevelStats::class,
                Widgets\ReportLevelStats::class,
            ])
            ->resources([
                $this->getReportResourceClass(),
                $this->getRemarkResourceClass(),
            ]);
    }

    public function getReportResourceClass(): string
    {
        return $this->reportResource;
    }

    public function getRemarkResourceClass(): string
    {
        return $this->remarkResource;
    }

    public function getInspectableTypes(): array
    {
        return $this->inspectableTypes;
    }

    public function boot(Panel $panel): void
    {
        // Optional: Boot logic for the plugin
    }
}
