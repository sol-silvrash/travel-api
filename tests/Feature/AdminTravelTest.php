<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Travel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminTravelTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_cannot_access_adding_travel()
    {
        $response = $this->postJson('/api/v1/admin/travels');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_non_admin_user_cannot_access_adding_travel()
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels');
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_saves_travel_successfully_with_valid_data()
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels', [
            'name' => 'Travel name',
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels', [
            'name' => 'Travel name',
            'is_public' => 1,
            'description' => 'Some description',
            'number_of_days' => 5,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_updates_travel_successfully_with_valid_data()
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $travel = Travel::factory()->create();

        $response = $this->actingAs($user)->putJson("/api/v1/admin/travels/{$travel->id}", [
            'name' => 'Travel Name',
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->actingAs($user)->putJson("/api/v1/admin/travels/{$travel->id}", [
            'name' => 'Travel Name updated',
            'is_public' => 1,
            'description' => 'Some description',
            'number_of_days' => 5,
        ]);
        $response->assertStatus(Response::HTTP_OK);

        $response = $this->get('/api/v1/travels');
        $response->assertJsonFragment([
            'name' => 'Travel Name updated',
        ]);
    }
}
