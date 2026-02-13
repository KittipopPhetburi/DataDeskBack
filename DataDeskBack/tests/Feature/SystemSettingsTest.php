<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_system_settings()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->getJson('/api/system-settings');

        $response->assertStatus(200);
    }

    public function test_can_update_system_settings()
    {
        $user = User::factory()->create();
        
        $settings = [
            'settings' => [
                'systemName' => 'Test System',
                'supportEmail' => 'test@example.com',
                'autoAssign' => true,
            ]
        ];

        $response = $this->actingAs($user)->postJson('/api/system-settings', $settings);

        $response->assertStatus(200);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'systemName',
            'value' => 'Test System',
        ]);
    }
}
