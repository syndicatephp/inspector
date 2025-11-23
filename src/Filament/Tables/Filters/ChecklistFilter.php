<?php

namespace Syndicate\Inspector\Filament\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;
use Syndicate\Inspector\Checklists\BaseChecklist;
use Syndicate\Inspector\Checklists\ContentChecklist;
use Syndicate\Inspector\Checklists\PerformanceChecklist;
use Syndicate\Inspector\Checklists\SeoChecklist;

class ChecklistFilter extends SelectFilter
{
    public static function make(?string $name = 'checklist'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->options($this->getChecklists());
    }

    protected function getChecklists(): array
    {
        $checklists = [
            BaseChecklist::class,
            SeoChecklist::class,
            PerformanceChecklist::class,
            ContentChecklist::class
        ];

        return collect($checklists)
            ->sort()
            ->mapWithKeys(function ($name) {
                $label = str(class_basename($name))->before('Checklist')->headline()->toString();
                return [$name => $label];
            })->toArray();
    }
}


