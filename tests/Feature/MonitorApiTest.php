<?php

namespace Tests\Feature;

use App\Models\Monitor;
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
}
