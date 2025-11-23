<?php

namespace Syndicate\Inspector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Syndicate\Inspector\Enums\RemarkLevel;

class Remark extends Model
{
    protected $table = 'inspection_remarks';
    protected $guarded = ['id'];

    protected $casts = [
        'level' => RemarkLevel::class,
        'details' => 'array',
        'config' => 'array',
    ];

    public function inspectable(): MorphTo
    {
        return $this->morphTo();
    }
}
