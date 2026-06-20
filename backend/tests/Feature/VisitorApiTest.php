<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Visitor;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class VisitorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_log_visitor_with_device_details()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        ])->postJson('/api/visitors', [
            'device_id' => 'device-123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('visitors', [
            'device_id' => 'device-123',
        ]);
    }

    public function test_returning_visitor_updates_device_details()
    {
        // Simulate a visitor first logged without device data (or old data)
        Visitor::create([
            'device_id' => 'returning-device-456',
            'device_type' => 'unknown',
            'os' => null,
            'browser' => null,
            'device_name' => null,
        ]);

        // Same device returns, now we parse User-Agent
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ])->postJson('/api/visitors', [
            'device_id' => 'returning-device-456',
        ]);

        $response->assertStatus(201);

        // Ensure database count hasn't increased, but row updated
        $this->assertDatabaseCount('visitors', 1);
        $this->assertDatabaseHas('visitors', [
            'device_id' => 'returning-device-456',
            'device_type' => 'desktop',
        ]);
    }

    public function test_admin_can_get_all_visitors_paginated()
    {
        $admin = User::factory()->create();
        Visitor::factory()->count(15)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/visitors');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total'])
            ->assertJsonCount(10, 'data'); // First page has 10 items
    }

    public function test_admin_can_delete_single_visitor()
    {
        $admin = User::factory()->create();
        $visitor = Visitor::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/visitors/{$visitor->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('visitors', ['id' => $visitor->id]);
    }

    public function test_admin_can_clear_all_visitors()
    {
        $admin = User::factory()->create();
        Visitor::factory()->count(5)->create();

        $response = $this->actingAs($admin)->deleteJson('/api/admin/visitors');

        $response->assertStatus(200);
        $this->assertDatabaseCount('visitors', 0);
    }

    public function test_guest_cannot_delete_visitors()
    {
        $visitor = Visitor::factory()->create();
        $response = $this->deleteJson("/api/admin/visitors/{$visitor->id}");
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/admin/visitors');
        $response->assertStatus(401);
    }

    public function test_guest_cannot_get_visitors()
    {
        $response = $this->getJson('/api/admin/visitors');

        $response->assertStatus(401);
    }
    public function test_ignores_bots()
    {
        $response = $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
                         ->postJson('/api/visitors', [
            'device_id' => 'bot-device',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Bots are not logged.']);

        $this->assertDatabaseMissing('visitors', [
            'device_id' => 'bot-device',
        ]);
    }

    public function test_logs_location_for_new_visitor()
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Indonesia',
                'regionName' => 'Jakarta',
                'city' => 'Jakarta',
                'isp' => 'Telkomsel',
            ], 200)
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '202.1.2.3'])
                         ->postJson('/api/visitors', [
            'device_id' => 'new-device-geo',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('visitors', [
            'device_id' => 'new-device-geo',
            'ip_address' => '202.1.2.3',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'isp' => 'Telkomsel',
        ]);
    }
    public function test_reads_ip_from_proxy_via_x_forwarded_for()
    {
        // Mock GeoIP response
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'United States',
                'regionName' => 'California',
                'city' => 'San Francisco',
                'isp' => 'Cloudflare',
            ], 200)
        ]);

        // Simulate request through a proxy (e.g., Render/Cloudflare)
        // REMOTE_ADDR is the proxy server's IP, but X-Forwarded-For is the real user's IP.
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
                         ->withHeaders(['X-Forwarded-For' => '198.51.100.10'])
                         ->postJson('/api/visitors', [
                             'device_id' => 'proxy-tested-device',
                         ]);

        $response->assertStatus(201);
        
        // Assert that the real IP (from X-Forwarded-For) is logged, NOT the proxy IP (10.0.0.5)
        $this->assertDatabaseHas('visitors', [
            'device_id' => 'proxy-tested-device',
            'ip_address' => '198.51.100.10',
            'city' => 'San Francisco',
        ]);
    }
}
