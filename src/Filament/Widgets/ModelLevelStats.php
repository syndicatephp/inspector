<?php

namespace Syndicate\Inspector\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Syndicate\Inspector\Enums\RemarkLevel;
use Syndicate\Inspector\Models\Remark;

class ModelLevelStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;
    protected static string $view = 'syndicate::inspector.widgets.level-stats';
    public string $inspectableType;
    public int $inspectableId;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $statusCounts = Remark::query()
            ->where('inspectable_type', $this->inspectableType)
            ->where('inspectable_id', $this->inspectableId)
            ->select('level', DB::raw('COUNT(*) as count'))
            ->groupBy('level')
            ->pluck('count', 'level');
        $total = $statusCounts->sum();
        $stats = [
            Stat::make('Total', $total)
                ->description('Total')
                ->color('gray')
        ];

        foreach (RemarkLevel::cases() as $level) {
            $figure = $statusCounts->get($level->value, 0);
            $percentage = $total === 0 ? $total : round(($figure / $total) * 100);
            $stats[] = Stat::make($percentage.'%', $figure)
                ->extraAttributes([
                    'title' => $level->getDescription(),
                ])
                ->description($level->getLabel())
                ->color($level->getColor());
        }

        return $stats;
    }
}
