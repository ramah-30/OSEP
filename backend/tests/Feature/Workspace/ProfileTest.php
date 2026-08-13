<?php

namespace Tests\Feature\Workspace;

use App\Enums\AccountType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    public function test_a_planner_can_update_their_profile(): void
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        Sanctum::actingAs($planner);

        $this->putJson('/api/v1/profile', [
            'company_name' => 'Elegant Events Ltd',
            'experience_years' => 8,
            'specialization' => 'Weddings',
        ])
            ->assertOk()
            ->assertJsonPath('data.profile.company_name', 'Elegant Events Ltd')
            ->assertJsonPath('data.profile.experience_years', 8);

        $this->assertDatabaseHas('planner_profiles', [
            'user_id' => $planner->id,
            'company_name' => 'Elegant Events Ltd',
        ]);
    }

    public function test_profile_validation_rejects_bad_input(): void
    {
        Sanctum::actingAs(User::factory()->accountType(AccountType::EventPlanner)->create());

        $this->putJson('/api/v1/profile', ['website' => 'not-a-url', 'experience_years' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['website', 'experience_years']);
    }

    public function test_a_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->accountType(AccountType::Client)->create();
        Sanctum::actingAs($user);

        // create() rather than image() avoids a hard dependency on the GD
        // extension in CI; the mime type still drives the `image` validation rule.
        $response = $this->postJson('/api/v1/profile/image', [
            'image' => UploadedFile::fake()->create('avatar.jpg', 200, 'image/jpeg'),
        ]);

        $response->assertOk()->assertJsonPath('data.avatar_url', fn ($url) => is_string($url));

        $this->assertNotEmpty(Storage::disk('public')->files('avatars'));
        $this->assertNotNull($user->refresh()->avatar_url);
    }

    public function test_upload_rejects_non_images(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/profile/image', [
            'image' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors(['image']);
    }
}
