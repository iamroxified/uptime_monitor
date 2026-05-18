<?php

namespace App\Services;

use App\Mail\MonitorDownMail;
use App\Mail\MonitorRecoveredMail;
use App\Models\Monitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class MonitorCheckService
{
    public function check(Monitor $monitor): void
    {
        $checkedAt = now();

        $statusCode = 0;
        $responseTimeMs = null;
        $isUp = false;

        try {
            $start = microtime(true);

            $response = Http::timeout((int) config('monitor.timeout_seconds', 10))
                ->get($monitor->url);

            $responseTimeMs = (int) round((microtime(true) - $start) * 1000);
            $statusCode = (int) $response->status();
            $isUp = $statusCode >= 200 && $statusCode < 400;
        } catch (ConnectionException $e) {
            // Connection/timeout/DNS failures count as DOWN with status_code=0.
        } catch (\Throwable $e) {
            // Treat unexpected HTTP client failures as DOWN.
        }

        $monitor->histories()->create([
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'is_up' => $isUp,
            'checked_at' => $checkedAt,
        ]);

        $monitor->forceFill([
            'last_checked_at' => $checkedAt,
        ])->save();

        if ($isUp) {
            $previousStatus = $monitor->status;

            if ($previousStatus !== 'up') {
                $monitor->forceFill(['status' => 'up'])->save();
            }

            if ($previousStatus === 'down') {
                $this->sendRecoveryEmail($monitor);
            }

            return;
        }

        $threshold = (int) $monitor->threshold;
        $recent = $monitor->histories()->latest('checked_at')->take($threshold)->get();
        $reachedThreshold = $recent->count() === $threshold && $recent->every(fn ($h) => ! $h->is_up);

        if ($reachedThreshold && $monitor->status !== 'down') {
            $monitor->forceFill(['status' => 'down'])->save();
            $this->sendDownEmail($monitor);
        }
    }

    private function sendDownEmail(Monitor $monitor): void
    {
        $to = config('monitor.alert_email');

        if (! is_string($to) || trim($to) === '') {
            return;
        }

        Mail::to($to)->queue(new MonitorDownMail($monitor));
    }

    private function sendRecoveryEmail(Monitor $monitor): void
    {
        $to = config('monitor.alert_email');

        if (! is_string($to) || trim($to) === '') {
            return;
        }

        Mail::to($to)->queue(new MonitorRecoveredMail($monitor));
    }
}
