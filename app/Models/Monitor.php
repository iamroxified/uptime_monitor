<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    protected $fillable = [
        'url',
        'check_interval',
        'threshold',
        'status',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function histories(): HasMany
    {
        return $this->hasMany(MonitorCheckHistory::class);
    }

    public function uptimePercentage(): ?float
    {
        $total = $this->histories()->count();

        if ($total === 0) {
            return null;
        }

        $up = $this->histories()->where('is_up', true)->count();

        return round(($up / $total) * 100, 2);
    }
}
