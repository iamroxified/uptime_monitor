<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Monitor;
use App\Services\MonitorCheckService;

class CheckMonitorJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $monitorId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $monitor = Monitor::find($this->monitorId);

        if (! $monitor) {
            return;
        }

        app(MonitorCheckService::class)->check($monitor);
    }
}
