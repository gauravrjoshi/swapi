<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tags_scoped_by_unid()
    {
        // User A and User B share UNID 2510
        $userA = User::factory()->create(['unid' => 2510]);
        $userB = User::factory()->create(['unid' => 2510]);

        // User C has a different UNID 2511
        $userC = User::factory()->create(['unid' => 2511]);

        $tagA = Tag::create(['name' => 'Food', 'color' => '#6366f1', 'user_id' => $userA->id]);
        $tagB = Tag::create(['name' => 'Rent', 'color' => '#10b981', 'user_id' => $userB->id]);
        $tagC = Tag::create(['name' => 'OtherTag', 'color' => '#f59e0b', 'user_id' => $userC->id]);

        Sanctum::actingAs($userA, ['*']);

        $response = $this->getJson('/api/v1/tags');

        $response->assertStatus(200)
            ->assertJsonCount(8)
            ->assertJsonFragment(['name' => 'Food'])
            ->assertJsonFragment(['name' => 'Rent'])
            ->assertJsonMissing(['name' => 'OtherTag']);
    }

    public function test_can_create_tag_successfully()
    {
        $user = User::factory()->create(['unid' => 2510]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'Travel',
            'color' => '#ec4899',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Travel',
                'color' => '#ec4899',
                'user_id' => $user->id,
            ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'Travel',
            'color' => '#ec4899',
            'user_id' => $user->id,
        ]);
    }

    public function test_cannot_create_duplicate_tag_within_same_unid()
    {
        $userA = User::factory()->create(['unid' => 2510]);
        $userB = User::factory()->create(['unid' => 2510]);

        // Tag created by another user in the same UNID
        Tag::create(['name' => 'Food', 'color' => '#6366f1', 'user_id' => $userB->id]);

        Sanctum::actingAs($userA, ['*']);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'Food',
            'color' => '#ef4444',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_create_duplicate_tag_in_different_unids()
    {
        $userA = User::factory()->create(['unid' => 2510]);
        $userC = User::factory()->create(['unid' => 2511]);

        // Tag created by a user in a different UNID
        Tag::create(['name' => 'Food', 'color' => '#6366f1', 'user_id' => $userC->id]);

        Sanctum::actingAs($userA, ['*']);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'Food',
            'color' => '#ef4444',
        ]);

        $response->assertStatus(201);
    }

    public function test_can_show_tag()
    {
        $user = User::factory()->create(['unid' => 2510]);
        $tag = Tag::create(['name' => 'Bills', 'color' => '#6366f1', 'user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tag->id,
                'name' => 'Bills',
            ]);
    }

    public function test_can_update_tag()
    {
        $user = User::factory()->create(['unid' => 2510]);
        $tag = Tag::create(['name' => 'Bills', 'color' => '#6366f1', 'user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Utilities',
            'color' => '#3b82f6',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tag->id,
                'name' => 'Utilities',
                'color' => '#3b82f6',
            ]);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Utilities',
            'color' => '#3b82f6',
        ]);
    }

    public function test_cannot_update_tag_with_duplicate_name_within_same_unid()
    {
        $userA = User::factory()->create(['unid' => 2510]);
        $userB = User::factory()->create(['unid' => 2510]);

        $tagA = Tag::create(['name' => 'Food', 'color' => '#6366f1', 'user_id' => $userA->id]);
        Tag::create(['name' => 'Rent', 'color' => '#10b981', 'user_id' => $userB->id]);

        Sanctum::actingAs($userA, ['*']);

        $response = $this->putJson("/api/v1/tags/{$tagA->id}", [
            'name' => 'Rent',
            'color' => '#3b82f6',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_tag_with_own_name()
    {
        $user = User::factory()->create(['unid' => 2510]);
        $tag = Tag::create(['name' => 'Bills', 'color' => '#6366f1', 'user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Bills',
            'color' => '#3b82f6',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tag->id,
                'name' => 'Bills',
                'color' => '#3b82f6',
            ]);
    }

    public function test_can_delete_tag()
    {
        $user = User::factory()->create(['unid' => 2510]);
        $tag = Tag::create(['name' => 'Shopping', 'color' => '#6366f1', 'user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_can_list_tags_including_default_tags()
    {
        $user = User::factory()->create(['unid' => 2510]);
        // Create 1 custom tag
        Tag::create(['name' => 'Z-CustomTag', 'color' => '#6366f1', 'user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/tags');

        // We have 6 default tags + 1 custom tag = 7 tags total
        $response->assertStatus(200)
            ->assertJsonCount(7)
            ->assertJsonFragment(['name' => 'Food & Drinks', 'id' => -1])
            ->assertJsonFragment(['name' => 'Salary', 'id' => -6])
            ->assertJsonFragment(['name' => 'Z-CustomTag']);
    }

    public function test_cannot_create_tag_with_default_tag_name()
    {
        $user = User::factory()->create(['unid' => 2510]);

        Sanctum::actingAs($user, ['*']);

        // Case-sensitive check
        $response = $this->postJson('/api/v1/tags', [
            'name' => 'Food & Drinks',
            'color' => '#ef4444',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Case-insensitive check
        $response2 = $this->postJson('/api/v1/tags', [
            'name' => 'food & drinks',
            'color' => '#ef4444',
        ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_update_default_tag()
    {
        $user = User::factory()->create(['unid' => 2510]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/tags/-1', [
            'name' => 'Modified Food',
            'color' => '#ef4444',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_delete_default_tag()
    {
        $user = User::factory()->create(['unid' => 2510]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/v1/tags/-1');

        $response->assertStatus(422);
    }
}
