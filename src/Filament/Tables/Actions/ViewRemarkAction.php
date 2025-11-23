<?php

namespace Syndicate\Inspector\Filament\Tables\Actions;

use Filament\Tables\Actions\ViewAction;
use Syndicate\Inspector\Filament\Infolists\RemarkDetails;

class ViewRemarkAction extends ViewAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->modalHeading(false)
            ->slideOver()
            ->infolist([
                RemarkDetails::make()
            ]);
    }

    public static function make(?string $name = 'view'): static
    {
        return parent::make($name);
    }
}
