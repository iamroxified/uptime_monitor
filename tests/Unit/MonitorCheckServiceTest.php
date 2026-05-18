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

        Http::fakeSequence()
            ->push('fail', 500)
            ->push('fail', 500)
            ->push('ok', 200);

        app(MonitorCheckService::class)->check($monitor->fresh());
        $this->assertSame('pending', $monitor->fresh()->status);

        app(MonitorCheckService::class)->check($monitor->fresh());
        $this->assertSame('down', $monitor->fresh()->status);
        Mail::assertQueued(MonitorDownMail::class, 1);

        app(MonitorCheckService::class)->check($monitor->fresh());
        $last = $monitor->fresh()->histories()->latest('checked_at')->first();
        $this->assertNotNull($last);
        $this->assertSame(200, $last->status_code);
        $this->assertTrue($last->is_up);
        $this->assertSame('up', $monitor->fresh()->status);
        Mail::assertQueued(MonitorRecoveredMail::class, 1);
    }
}
