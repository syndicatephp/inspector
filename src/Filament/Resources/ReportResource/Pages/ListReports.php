<?php

namespace Syndicate\Inspector\Filament\Resources\ReportResource\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\Relation;
use Syndicate\Inspector\Filament\Actions\BulkInspectModelAction;
use Syndicate\Inspector\Filament\InspectorPlugin;
use Syndicate\Inspector\Filament\Resources\ReportResource;
use Syndicate\Inspector\Filament\Tables\Actions\InspectRecordAction;
use Syndicate\Inspector\Filament\Tables\Actions\InspectRecordsBulkAction;
use Syndicate\Inspector\Filament\Tables\Columns\LevelColumn;
use Syndicate\Inspector\Filament\Widgets\ReportLevelStats;
use Syndicate\Inspector\Models\Report;
use Syndicate\Inspector\Services\InspectorService;

class ListReports extends ListRecords
{
    use ExposesTableToWidgets;

    public static function getResource(): string
    {
        $currentPanel = Filament::getCurrentPanel();
        $plugin = null;

        if ($currentPanel && $currentPanel->hasPlugin('inspector')) {
            $plugin = $currentPanel->getPlugin('inspector');
        }

        /** @var ?InspectorPlugin $plugin */
        return $plugin?->getReportResourceClass() ?? ReportResource::class;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Type & Title')
                    ->wrap()
                    ->formatStateUsing(function (Report $record): string {
                        if ($record->inspectable_type === null) {
                            return $record->url;
                        }
                        return class_basename(Relation::getMorphedModel($record->inspectable_type)) . ': ' . $record->inspectable_id;
                    }),
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
            ])
            ->filters([
//                InspectableFilter::make(),
//                LevelFilter::make(),
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
            Action::make('inspect')
                ->requiresConfirmation()
                ->form([
                    TextInput::make('url')
                        ->required()
                        ->url()
                ])
                ->action(function (array $data): void {
                    resolve(InspectorService::class)->inspectUrl($data['url']);
                })
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ReportLevelStats::class
        ];
    }
}
