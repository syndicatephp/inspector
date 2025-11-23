<?php

namespace Syndicate\Inspector\Filament\Resources\RemarkResource\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Syndicate\Assistant\Enums\FilamentPageType;
use Syndicate\Assistant\Filament\Tables\Columns\CreatedAtColumn;
use Syndicate\Carpenter\Filament\Tables\Columns\MorphModelColumn;
use Syndicate\Inspector\Filament\Actions\BulkInspectModelAction;
use Syndicate\Inspector\Filament\InspectorPlugin;
use Syndicate\Inspector\Filament\Resources\RemarkResource;
use Syndicate\Inspector\Filament\Tables\Actions\InspectRecordAction;
use Syndicate\Inspector\Filament\Tables\Actions\InspectRecordsBulkAction;
use Syndicate\Inspector\Filament\Tables\Actions\OpenLinkAction;
use Syndicate\Inspector\Filament\Tables\Actions\ViewRemarkAction;
use Syndicate\Inspector\Filament\Tables\Columns\CheckColumn;
use Syndicate\Inspector\Filament\Tables\Columns\ChecklistColumn;
use Syndicate\Inspector\Filament\Tables\Columns\LevelColumn;
use Syndicate\Inspector\Filament\Tables\Columns\MessageColumn;
use Syndicate\Inspector\Filament\Tables\Filters\CheckFilter;
use Syndicate\Inspector\Filament\Tables\Filters\ChecklistFilter;
use Syndicate\Inspector\Filament\Tables\Filters\InspectableFilter;
use Syndicate\Inspector\Filament\Tables\Filters\LevelFilter;
use Syndicate\Inspector\Filament\Widgets\RemarkLevelStats;

class ListRemarks extends ListRecords
{
    use ExposesTableToWidgets;

    public static function getResource(): string
    {
        $currentPanel = Filament::getCurrentPanel();
        $plugin = null;

        if ($currentPanel && $currentPanel->hasPlugin('syndicate-inspector')) {
            $plugin = $currentPanel->getPlugin('syndicate-inspector');
        }

        /** @var ?InspectorPlugin $plugin */
        return $plugin?->getRemarkResourceClass() ?? RemarkResource::class;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                MorphModelColumn::make('inspectable.id')
                    ->link(FilamentPageType::ViewInspection),
                CheckColumn::make(),
                ChecklistColumn::make(),
                LevelColumn::make(),
                MessageColumn::make(),
                CreatedAtColumn::make()
            ])
            ->filters([
                InspectableFilter::make(),
                LevelFilter::make(),
                CheckFilter::make(),
                ChecklistFilter::make(),
            ])
            ->deferFilters(false)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    InspectRecordAction::make(),
                    ViewRemarkAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    OpenLinkAction::make()
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    InspectRecordsBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([25, 50]);
    }

    protected function getHeaderActions(): array
    {
        return [
            BulkInspectModelAction::make()
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RemarkLevelStats::class,
        ];
    }
}
