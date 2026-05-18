<?php

namespace Tests\Feature;

use App\Models\MonitorCheckHistory;
use App\Models\Monitor;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_monitor(): void
    {
        $response = $this->postJson('/api/monitors', [
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'url',
                    'check_interval',
                    'threshold',
                    'status',
                    'last_checked_at',
                    'uptime_percentage',
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.url', 'https://example.com')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_duplicate_url_returns_422(): void
    {
        Monitor::create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'pending',
        ]);

        $this->postJson('/api/monitors', [
            'url' => 'https://example.com',
        ])->assertStatus(422);
    }

    public function test_can_list_monitors(): void
    {
        Monitor::create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'pending',
        ]);

        $this->getJson('/api/monitors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.url', 'https://example.com');
    }

    public function test_history_for_unknown_monitor_returns_custom_message(): void
    {
        $this->getJson('/api/monitors/999999/history')
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Monitor not found.']);
    }

    public function test_history_returns_meta_and_caps_per_page_to_100(): void
    {
        $monitor = Monitor::create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'pending',
        ]);

        $now = Carbon::parse('2026-05-18 00:00:00');

        for ($i = 0; $i < 150; $i++) {
            $monitor->histories()->create([
                'status_code' => 200,
                'response_time_ms' => 10,
                'is_up' => true,
                'checked_at' => $now->copy()->addSeconds($i),
            ]);
        }

        $response = $this->getJson("/api/monitors/{$monitor->id}/history?per_page=500");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.total', 150);

        $this->assertCount(100, $response->json('data'));
    }

    public function test_uptime_percentage_is_computed_when_history_exists(): void
    {
        $monitor = Monitor::create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'pending',
        ]);

        $monitor->histories()->create([
            'status_code' => 200,
            'response_time_ms' => 10,
            'is_up' => true,
            'checked_at' => now()->subMinute(),
        ]);

        $monitor->histories()->create([
            'status_code' => 500,
            'response_time_ms' => 10,
            'is_up' => false,
            'checked_at' => now(),
        ]);

        $this->getJson('/api/monitors')
            ->assertOk()
            ->assertJsonPath('data.0.uptime_percentage', 50);
    }
}
