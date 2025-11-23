<?php

namespace Vvb13a\Sanity\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Vvb13a\Sanity\Enums\FindingLevel;
use Vvb13a\Sanity\Models\Finding;

class RecordFindingStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    protected static string $view = 'sanity::widgets.findings-stats';
    public string $checkableType;
    public int $checkableId;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $statusCounts = Finding::query()
            ->where('checkable_type', $this->checkableType)
            ->where('checkable_id', $this->checkableId)
            ->select('level', DB::raw('COUNT(*) as count'))
            ->groupBy('level')
            ->pluck('count', 'level');

        $total = $statusCounts->sum();
        $stats = [
            Stat::make('Total', $total)
                ->description('Total')
                ->color('gray')
        ];

        foreach (FindingLevel::cases() as $level) {
            $figure = $statusCounts->get($level->value, 0);
            $percentage = $total === 0 ? $total : round(($figure / $total) * 100);
            $stats[] = Stat::make($percentage . '%', $figure)
                ->description($level->getLabel())
                ->extraAttributes([
                    'title' => $level->description(),
                ])
                ->color($level->getColor());
        }

        return $stats;
    }
}
