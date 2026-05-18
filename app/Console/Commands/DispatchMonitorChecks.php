<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitorJob;
use App\Models\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DispatchMonitorChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitors:dispatch-checks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch due monitor checks to the queue.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        Monitor::query()
            ->select(['id', 'check_interval', 'last_checked_at'])
            ->orderBy('id')
            ->chunkById(200, function ($monitors) use ($now) {
                foreach ($monitors as $monitor) {
                    $due = $monitor->last_checked_at === null
                        || $monitor->last_checked_at->lte($now->copy()->subMinutes($monitor->check_interval));

                    if (! $due) {
                        continue;
                    }

                    $lockSeconds = max(30, (int) $monitor->check_interval * 60);

                    Cache::lock("monitor:check:{$monitor->id}", $lockSeconds)
                        ->get(fn () => dispatch(new CheckMonitorJob($monitor->id)));
                }
            });

        return self::SUCCESS;
    }
}
