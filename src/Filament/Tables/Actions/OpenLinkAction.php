<?php

namespace Syndicate\Inspector\Filament\Tables\Actions;

use Filament\Tables\Actions\ViewAction;
use Syndicate\Inspector\Models\Remark;
use Syndicate\Inspector\Models\Report;

class OpenLinkAction extends ViewAction
{
    public static function make(?string $name = 'open_link'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('info')
            ->label('Open')
            ->url(function (Remark|Report $record): string {
                return $record->url;
            }, shouldOpenInNewTab: true);
    }
}
