<?php

namespace Syndicate\Inspector\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use LogicException;
use Syndicate\Inspector\DTOs\ModelInspectionReport;
use Syndicate\Inspector\Enums\RemarkLevel;
use Syndicate\Inspector\Jobs\InspectionJob;
use Syndicate\Inspector\Models\Remark;
use Syndicate\Inspector\Models\Report;

/**
 * @mixin Model
 *
 * @property-read Collection|Remark[] $inspectionRemarks
 * @property-read Report|null $inspectionReport
 */
trait Inspectable
{
    public static function bootInspectable(): void
    {
        static::deleting(function ($model) {
            $model->inspectionRemarks()->delete();
            $model->inspectionReport()->delete();
        });
    }

    public function inspectionRemarks(): MorphMany
    {
        return $this->morphMany(Remark::class, 'inspectable');
    }

    public function inspectionReport(): MorphOne
    {
        return $this->morphOne(Report::class, 'inspectable');
    }

    public static function modelInspectionReport(): ModelInspectionReport
    {
        $levelCountsFromDb = Report::query()
            ->where('inspectable_type', Relation::getMorphAlias(static::class))
            ->select('level', DB::raw('COUNT(*) as count'))
            ->groupBy('level')
            ->pluck('count', 'level');

        return new ModelInspectionReport(
            modelClass: static::class,
            success: $levelCountsFromDb->get(RemarkLevel::SUCCESS->value) ?? 0,
            info: $levelCountsFromDb->get(RemarkLevel::INFO->value) ?? 0,
            notice: $levelCountsFromDb->get(RemarkLevel::NOTICE->value) ?? 0,
            warning: $levelCountsFromDb->get(RemarkLevel::WARNING->value) ?? 0,
            error: $levelCountsFromDb->get(RemarkLevel::ERROR->value) ?? 0,
            fatal: $levelCountsFromDb->get(RemarkLevel::FATAL->value) ?? 0,
        );
    }

    public function queueChecks($user = null): void
    {
        if ($this->getKey() === null) {
            throw new LogicException("Cannot queue checks for a model that has not been persisted yet (missing ID).");
        }

        InspectionJob::dispatch(model: $this, user: $user);
    }

    public function runChecksNow($user = null): void
    {
        if ($this->getKey() === null) {
            throw new LogicException("Cannot run checks for a model that has not been persisted yet (missing ID).");
        }

        InspectionJob::dispatchSync(model: $this, user: $user);
    }
}
