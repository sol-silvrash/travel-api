<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\Travel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ToursListTest extends TestCase
{
    use RefreshDatabase;

    public function test_tours_list_by_travel_slug_returns_correct_tours()
    {
        $travel = Travel::factory()->create();
        $tour = Tour::factory()->create(['travel_id' => $travel->id]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $tour->id]);
    }

    public function test_tour_price_is_shown_correctly()
    {
        $travel = Travel::factory()->create();
        Tour::factory()->create([
            'travel_id' => $travel->id,
            'price_in_cents' => 12345,
        ]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['price' => '123.45']);
    }

    public function test_tours_list_returns_pagination()
    {
        $toursPerPage = config('custom.pagination.default');

        $travel = Travel::factory()->create();
        Tour::factory($toursPerPage + 1)->create(['travel_id' => $travel->id]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount($toursPerPage, 'data');
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_tours_lists_sorts_by_starting_date_correctly()
    {
        $travel = Travel::factory()->create();

        $earlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now(),
            'ending_date' => now()->addDays(value: 1),
        ]);

        $laterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonPath('data.0.id', $earlierTour->id);
        $response->assertJsonPath('data.1.id', $laterTour->id);
    }

    public function test_tours_list_sorts_by_price_correctly()
    {
        $travel = Travel::factory()->create();

        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price_in_cents' => 20000,
        ]);

        $cheapEarlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price_in_cents' => 10000,
            'starting_date' => now(),
            'ending_date' => now()->addDays(1),
        ]);

        $cheapLaterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price_in_cents' => 10000,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours?sortBy=price&sortOrder=asc");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonPath('data.0.id', $cheapEarlierTour->id);
        $response->assertJsonPath('data.1.id', $cheapLaterTour->id);
        $response->assertJsonPath('data.2.id', $expensiveTour->id);
    }

    public function test_tours_list_filters_by_price_correctly()
    {
        $travel = Travel::factory()->create();

        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price_in_cents' => 20000,
        ]);

        $cheapTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price_in_cents' => 10000,
        ]);

        $endpoint = "/api/v1/travels/{$travel->slug}/tours";

        $response = $this->get("$endpoint?priceFrom=100");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get("$endpoint?priceFrom=150");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get("$endpoint?priceFrom=250");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(0, 'data');

        $response = $this->get("$endpoint?priceTo=200");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get("$endpoint?priceTo=150");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonMissing(['id' => $expensiveTour->id]);

        $response = $this->get("$endpoint?priceTo=50");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(0, 'data');

        $response = $this->get("$endpoint?priceFrom=150&priceTo=250");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);
    }

    public function test_tours_list_filters_by_starting_date()
    {
        $travel = Travel::factory()->create();

        $earlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now(),
            'ending_date' => now()->addDays(value: 1),
        ]);

        $laterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);

        $endpoint = "/api/v1/travels/{$travel->slug}/tours";

        $response = $this->get("$endpoint?dateFrom=" . now());
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get("$endpoint?dateFrom=" . now()->addDay());
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get("$endpoint?dateFrom=" . now()->addDays(5));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(0, 'data');

        $response = $this->get("$endpoint?dateTo=" . now()->addDays(5));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get("$endpoint?dateTo=" . now()->addDay());
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonMissing(['id' => $laterTour->id]);

        $response = $this->get("$endpoint?dateTo=" . now()->subDay());
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(0, 'data');

        $response = $this->get("$endpoint?dateFrom=" . now()->addDay() . '&dateTo=' . now()->addDays(5));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);
    }

    public function test_tour_list_returns_validation_errors()
    {
        $travel = Travel::factory()->create();

        $response = $this->getJson("/api/v1/travels/$travel->slug/tours?dateFrom=abcde");
        $response->assertStatus(422);

        $response = $this->getJson("/api/v1/travels/$travel->slug/tours?priceFrom=abcde");
        $response->assertStatus(422);
    }
}
