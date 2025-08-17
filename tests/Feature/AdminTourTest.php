<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Travel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_cannot_access_adding_tour()
    {
        $travel = Travel::factory()->create();

        $response = $this->postJson("/api/v1/admin/travels/{$travel->id}/tours");
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_non_admin_user_cannot_access_adding_tour()
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $travel = Travel::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/admin/travels/{$travel->id}/tours");
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_saves_travel_successfully_with_valid_data()
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $travel = Travel::factory()->create();
        $response = $this->actingAs($user)->postJson("/api/v1/admin/travels/{$travel->id}/tours", [
            'name' => 'Tour Name',
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $travel = Travel::factory()->create();
        $response = $this->actingAs($user)->postJson("/api/v1/admin/travels/{$travel->id}/tours", [
            'name' => 'Tour Name',
            'starting_date' => now()->toDateString(),
            'ending_date' => now()->addDay()->toDateString(),
            'price_in_cents' => 15000,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");
        $response->assertJsonFragment(['name' => 'Tour Name']);
    }
}
