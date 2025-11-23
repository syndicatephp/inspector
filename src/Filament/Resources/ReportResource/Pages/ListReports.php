<?php

namespace Syndicate\Inspector\Filament\Resources\ReportResource\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Syndicate\Assistant\Enums\FilamentPageType;
use Syndicate\Assistant\Filament\Tables\Columns\UpdatedAtColumn;
use Syndicate\Carpenter\Filament\Tables\Columns\MorphModelColumn;
use Syndicate\Inspector\Filament\Actions\BulkInspectModelAction;
use Syndicate\Inspector\Filament\InspectorPlugin;
use Syndicate\Inspector\Filament\Resources\ReportResource;
use Syndicate\Inspector\Filament\Tables\Actions\InspectRecordAction;
use Syndicate\Inspector\Filament\Tables\Actions\InspectRecordsBulkAction;
use Syndicate\Inspector\Filament\Tables\Columns\LevelColumn;
use Syndicate\Inspector\Filament\Tables\Filters\InspectableFilter;
use Syndicate\Inspector\Filament\Tables\Filters\LevelFilter;
use Syndicate\Inspector\Filament\Widgets\ReportLevelStats;
use Syndicate\Inspector\Models\Report;

class ListReports extends ListRecords
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
        return $plugin?->getReportResourceClass() ?? ReportResource::class;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                MorphModelColumn::make('inspectable.id')
                    ->link(FilamentPageType::ViewInspection),
                LevelColumn::make(),
                TextColumn::make('finding_counts')
                    ->formatStateUsing(function (Report $record) {
                        return $record->finding_counts->total();
                    })
                    ->label('Remarks'),
                TextColumn::make('check_counts')
                    ->formatStateUsing(function (Report $record) {
                        return $record->check_counts->total();
                    })
                    ->label('Checks'),
                UpdatedAtColumn::make()
            ])
            ->filters([
                InspectableFilter::make(),
                LevelFilter::make(),
            ])
            ->deferFilters(false)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    InspectRecordAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    InspectRecordsBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginationPageOptions([25, 50]);
    }

    protected function getHeaderActions(): array
    {
        return [
            BulkInspectModelAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ReportLevelStats::class
        ];
    }
}
