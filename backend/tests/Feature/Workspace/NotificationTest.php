<?php

namespace Tests\Feature\Workspace;

use App\Models\Notification;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    public function test_index_returns_notifications_with_unread_count(): void
    {
        $user = User::factory()->create();
        Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'A', 'message' => 'x']);
        Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'B', 'message' => 'y', 'read_at' => now()]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonCount(2, 'data.notifications');
    }

    public function test_a_user_can_mark_a_notification_read(): void
    {
        $user = User::factory()->create();
        $n = Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'A', 'message' => 'x']);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/notifications/{$n->id}/read")
            ->assertOk()
            ->assertJsonPath('data.notification.read', true)
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_a_user_cannot_read_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $n = Notification::create(['user_id' => $owner->id, 'type' => 'test', 'title' => 'A', 'message' => 'x']);

        Sanctum::actingAs($other);

        $this->putJson("/api/v1/notifications/{$n->id}/read")->assertStatus(404);
    }

    public function test_a_user_can_delete_their_notification(): void
    {
        $user = User::factory()->create();
        $unread = Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'A', 'message' => 'x']);
        Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'B', 'message' => 'y']);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/notifications/{$unread->id}")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->assertDatabaseMissing('notifications', ['id' => $unread->id]);
    }

    public function test_a_user_cannot_delete_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $n = Notification::create(['user_id' => $owner->id, 'type' => 'test', 'title' => 'A', 'message' => 'x']);

        Sanctum::actingAs($other);

        $this->deleteJson("/api/v1/notifications/{$n->id}")->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $n->id]);
    }
}
