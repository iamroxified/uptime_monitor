<?php

namespace Tests\Unit;

use App\Mail\MonitorDownMail;
use App\Mail\MonitorRecoveredMail;
use App\Models\Monitor;
use App\Services\MonitorCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MonitorCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_monitor_down_only_after_threshold_and_sends_emails(): void
    {
        config()->set('monitor.alert_email', 'alerts@example.com');

        $monitor = Monitor::create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 2,
            'status' => 'pending',
        ]);

        Mail::fake();

        Http::fake([
            '*' => Http::response('fail', 500),
        ]);

        app(MonitorCheckService::class)->check($monitor->fresh());
        $this->assertSame('pending', $monitor->fresh()->status);

        app(MonitorCheckService::class)->check($monitor->fresh());
        $this->assertSame('down', $monitor->fresh()->status);
        Mail::assertQueued(MonitorDownMail::class, 1);

        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        app(MonitorCheckService::class)->check($monitor->fresh());
        $this->assertSame('up', $monitor->fresh()->status);
        Mail::assertQueued(MonitorRecoveredMail::class, 1);
    }
}
