<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorCheckHistory extends Model
{
    protected $fillable = [
        'monitor_id',
        'status_code',
        'response_time_ms',
        'is_up',
        'checked_at',
    ];

    protected $casts = [
        'is_up' => 'bool',
        'checked_at' => 'datetime',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
