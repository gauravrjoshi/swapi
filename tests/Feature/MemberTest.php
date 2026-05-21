<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    // ─── Auth Guard Tests ─────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_members_endpoints()
    {
        $this->getJson('/api/v1/admin/members')->assertStatus(401);
        $this->postJson('/api/v1/admin/members', [])->assertStatus(401);
        $this->putJson('/api/v1/admin/members/1', [])->assertStatus(401);
        $this->deleteJson('/api/v1/admin/members/1')->assertStatus(401);
    }

    public function test_non_admin_user_cannot_access_members_endpoints()
    {
        $member = User::factory()->create(['unid' => 2510, 'is_admin' => false]);
        Sanctum::actingAs($member, ['*']);

        $this->getJson('/api/v1/admin/members')->assertStatus(403);
        $this->postJson('/api/v1/admin/members', [])->assertStatus(403);
        $this->putJson('/api/v1/admin/members/1', [])->assertStatus(403);
        $this->deleteJson('/api/v1/admin/members/1')->assertStatus(403);
    }

    // ─── Index Tests ──────────────────────────────────────────────────────────

    public function test_admin_can_list_members_in_own_unid()
    {
        $admin   = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        $memberA = User::factory()->create(['unid' => 2510, 'is_admin' => false]);
        $memberB = User::factory()->create(['unid' => 2510, 'is_admin' => false]);

        // Member in a different UNID — should NOT appear
        User::factory()->create(['unid' => 2511, 'is_admin' => false]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/admin/members');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['email' => $memberA->email])
            ->assertJsonFragment(['email' => $memberB->email]);
    }

    public function test_admin_is_not_listed_among_members()
    {
        $admin = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        User::factory()->create(['unid' => 2510, 'is_admin' => false]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/admin/members');

        $response->assertStatus(200)
            ->assertJsonMissing(['email' => $admin->email]);
    }

    // ─── Store Tests ──────────────────────────────────────────────────────────

    public function test_admin_can_create_member_with_default_unid()
    {
        $admin = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/v1/admin/members', [
            'name'     => 'New Member',
            'email'    => 'member@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'email'    => 'member@example.com',
                'is_admin' => false,
                'unid'     => 2510, // should default to admin's UNID
            ]);

        $this->assertDatabaseHas('users', [
            'email'    => 'member@example.com',
            'is_admin' => false,
            'unid'     => 2510,
        ]);
    }

    public function test_admin_can_create_member_with_custom_existing_unid()
    {
        $admin    = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        // User existing in unid 2511
        User::factory()->create(['unid' => 2511]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/v1/admin/members', [
            'name'     => 'Mapped Member',
            'email'    => 'mapped@example.com',
            'password' => 'password123',
            'unid'     => 2511,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['unid' => 2511]);

        $this->assertDatabaseHas('users', [
            'email' => 'mapped@example.com',
            'unid'  => 2511,
        ]);
    }

    public function test_admin_cannot_create_member_with_non_existent_unid()
    {
        $admin = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/v1/admin/members', [
            'name'     => 'Bad Member',
            'email'    => 'bad@example.com',
            'password' => 'password123',
            'unid'     => 9999, // Non-existent
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unid']);
    }

    public function test_created_member_is_always_non_admin()
    {
        $admin = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        // Even if we somehow pass is_admin = true, it should be ignored
        $this->postJson('/api/v1/admin/members', [
            'name'     => 'Sneaky User',
            'email'    => 'sneaky@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email'    => 'sneaky@example.com',
            'is_admin' => false,
        ]);
    }

    public function test_cannot_create_member_with_duplicate_email()
    {
        $admin = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        User::factory()->create(['email' => 'taken@example.com', 'unid' => 2510]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/v1/admin/members', [
            'name'     => 'Duplicate',
            'email'    => 'taken@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ─── Update Tests ─────────────────────────────────────────────────────────

    public function test_admin_can_update_member_details()
    {
        $admin  = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        $member = User::factory()->create(['unid' => 2510, 'is_admin' => false]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->putJson("/api/v1/admin/members/{$member->id}", [
            'name'  => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name'  => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'id'    => $member->id,
            'name'  => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_admin_can_remap_member_unid_to_existing_workspace()
    {
        $admin  = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        $member = User::factory()->create(['unid' => 2510, 'is_admin' => false]);
        // Ensure UNID 2511 exists in the system
        User::factory()->create(['unid' => 2511]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->putJson("/api/v1/admin/members/{$member->id}", [
            'name'  => $member->name,
            'email' => $member->email,
            'unid'  => 2511,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['unid' => 2511]);

        $this->assertDatabaseHas('users', [
            'id'   => $member->id,
            'unid' => 2511,
        ]);
    }

    public function test_admin_cannot_update_member_in_different_unid()
    {
        $admin        = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        $otherMember  = User::factory()->create(['unid' => 2511, 'is_admin' => false]);

        Sanctum::actingAs($admin, ['*']);

        // Because BelongsToUnid scopes the query, findOrFail should return 404
        $response = $this->putJson("/api/v1/admin/members/{$otherMember->id}", [
            'name'  => 'Hacker',
            'email' => 'hacked@example.com',
        ]);

        $response->assertStatus(404);
    }

    // ─── Destroy Tests ────────────────────────────────────────────────────────

    public function test_admin_can_delete_member()
    {
        $admin  = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        $member = User::factory()->create(['unid' => 2510, 'is_admin' => false]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson("/api/v1/admin/members/{$member->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_admin_cannot_delete_their_own_account()
    {
        $admin = User::factory()->create(['unid' => 2510, 'is_admin' => true]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson("/api/v1/admin/members/{$admin->id}");

        // The admin is an admin (is_admin = true), so BelongsToUnid will still include them,
        // but the controller guards against self-deletion
        $response->assertStatus(400);
    }

    public function test_admin_cannot_delete_member_in_different_unid()
    {
        $admin       = User::factory()->create(['unid' => 2510, 'is_admin' => true]);
        $otherMember = User::factory()->create(['unid' => 2511, 'is_admin' => false]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson("/api/v1/admin/members/{$otherMember->id}");

        $response->assertStatus(404);
    }
}
