<?php

namespace Syndicate\Inspector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Syndicate\Inspector\Casts\AsLevelCounts;
use Syndicate\Inspector\Enums\RemarkLevel;

class Report extends Model
{
    protected $table = 'inspection_reports';

    protected $guarded = ['id'];

    protected $casts = [
        'level' => RemarkLevel::class,
        'check_counts' => AsLevelCounts::class,
        'finding_counts' => AsLevelCounts::class,
    ];

    public function inspectable(): MorphTo
    {
        return $this->morphTo();
    }
}
