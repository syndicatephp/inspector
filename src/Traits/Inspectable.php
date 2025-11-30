<?php

namespace Syndicate\Inspector\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Syndicate\Inspector\Contracts\Inspection;
use Syndicate\Inspector\DTOs\ModelInspectionReport;
use Syndicate\Inspector\Enums\RemarkLevel;
use Syndicate\Inspector\Jobs\InspectionJob;
use Syndicate\Inspector\Models\Remark;
use Syndicate\Inspector\Models\Report;
use Syndicate\Inspector\Services\InspectorService;

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

    public function inspect(): void
    {
        if ($this->getKey() === null) {
            throw new LogicException("Cannot perform checks for a non-persisted model.");
        }

        resolve(InspectorService::class)->inspectModel($this);
    }

    public function inspectAsync(): void
    {
        if ($this->getKey() === null) {
            throw new LogicException("Cannot queue checks for a non-persisted model.");
        }

        InspectionJob::dispatch($this->inspection());
    }

    public function inspection(): Inspection
    {
        if (property_exists($this, 'inspectionClass')) {
            return resolve($this->inspectionClass, ['model' => $this]);
        }

        $guess = 'App\\Syndicate\\Inspector\\Inspections\\' . class_basename($this) . 'Inspection';

        if (class_exists($guess)) {
            return resolve($guess, ['model' => $this]);
        }

        throw new RuntimeException("Could not find Inspection for model " . get_class($this));
    }
}
