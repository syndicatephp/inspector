<?php

namespace Syndicate\Inspector\Filament\Pages;

use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Syndicate\Inspector\Filament\Actions\ClearInspectionAction;
use Syndicate\Inspector\Filament\Actions\InspectModelAction;
use Syndicate\Inspector\Filament\Tables\Actions\OpenLinkAction;
use Syndicate\Inspector\Filament\Tables\Actions\ViewRemarkAction;
use Syndicate\Inspector\Filament\Tables\Columns\CheckColumn;
use Syndicate\Inspector\Filament\Tables\Columns\ChecklistColumn;
use Syndicate\Inspector\Filament\Tables\Columns\LevelColumn;
use Syndicate\Inspector\Filament\Tables\Columns\MessageColumn;

class ViewInspection extends ManageRelatedRecords
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Inspection';
    protected static ?string $title = 'View Inspection';
    protected static ?string $breadcrumb = 'Inspection';
    protected static string $relationship = 'inspectionRemarks';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('check')
            ->columns([
                CheckColumn::make(),
                ChecklistColumn::make(),
                LevelColumn::make(),
                MessageColumn::make(),
            ])
            ->filters([
//                LevelFilter::make(),
//                CheckFilter::make(),
//                ChecklistFilter::make(),
            ])
            ->deferFilters(false)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    ViewRemarkAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    OpenLinkAction::make()
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('level_severity', 'desc')
            ->paginationPageOptions([25, 50]);
    }

//    protected function getHeaderWidgets(): array
//    {
//        return [
//            ModelLevelStats::make([
//                'inspectableType' => $this->getRecord()->getMorphClass(),
//                'inspectableId' => $this->getRecord()->getKey(),
//            ])
//        ];
//    }

    protected function getHeaderActions(): array
    {
        return [
            InspectModelAction::make(),
            ClearInspectionAction::make(),
        ];
    }
}
