<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\Travel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ToursListTest extends TestCase
{
    use RefreshDatabase;

    public function test_tours_list_by_travel_slug_returns_correct_tours(): void
    {
        $travel = Travel::factory()->create();
        $tour = Tour::factory()->create(['travel_id' => $travel->id]);

        $response = $this->get('/api/v1/travel/'.$travel->slug.'/tours');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $tour->id] );
    }

    public function test_tours_price_is_shown_correctly(): void
    {
        $travel = Travel::factory()->create();
        Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => '125.45',
        ]);

        $response = $this->get('/api/v1/travel/'.$travel->slug.'/tours');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['price' => '125.45']);
    }

    public function test_tours_list_returns_paginated_data_correctly(): void
    {
        $travel = Travel::factory()->create();
        Tour::factory(16)->create(['travel_id'=> $travel->id]);
        
        $response = $this->get('/api/v1/travel/'.$travel->slug.'/tours');

        $response->assertStatus(200);
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_tours_list_sorts_by_start_date_correctly(): void
    {
        $travel = Travel::factory()->create();
        $earlierTour = Tour::factory()->create([
            'travel_id'=> $travel->id,
            'start_date' => now(),
            'end_date' => now()->addDay(),
        ]);
        $laterTour = Tour::factory()->create([
            'travel_id'=> $travel->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
        ]);
        
        $response = $this->get('/api/v1/travel/'.$travel->slug.'/tours');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $earlierTour->id);
        $response->assertJsonPath('data.1.id', $laterTour->id);
    }

    public function test_tours_list_sorts_by_price_correctly(): void
    {
        $travel = Travel::factory()->create();
        $expensiveTour = Tour::factory()->create([
            'travel_id'=> $travel->id,
            'price' => 200,
        ]);
        $cheapEarlierTour = Tour::factory()->create([
            'travel_id'=> $travel->id,
            'price' => 100,
            'start_date' => now(),
            'end_date' => now()->addDay(),
        ]);
        $cheapLaterTour = Tour::factory()->create([
            'travel_id'=> $travel->id,
            'price' => 100,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
        ]);
        
        $response = $this->get('/api/v1/travel/'.$travel->slug.'/tours?sortBy=price&sortOrder=asc');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $cheapEarlierTour->id);
        $response->assertJsonPath('data.1.id', $cheapLaterTour->id);
        $response->assertJsonPath('data.2.id', $expensiveTour->id);
    }

    public function test_tours_list_filters_by_price_correctly(): void
    {
        $travel = Travel::factory()->create();
        $expensiveTour = Tour::factory()->create([
            'travel_id'=> $travel->id,
            'price' => 200,
        ]);
        $cheapTour = Tour::factory()->create([
            'travel_id'=> $travel->id,
            'price' => 100,
        ]);
        $endpoint = '/api/v1/travel/'.$travel->slug.'/tours';

        $response = $this->get($endpoint . '?priceFrom=100');
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint . '?priceFrom=150');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);
        
        $response = $this->get($endpoint . '?priceFrom=250');
        $response->assertJsonCount(0, 'data');


        $response = $this->get($endpoint . '?priceTo=200');
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint . '?priceTo=150');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonMissing(['id' => $expensiveTour->id]);
        
        $response = $this->get($endpoint . '?priceTo=50');
        $response->assertJsonCount(0, 'data');


        $response = $this->get($endpoint . '?priceFrom=150&priceTo=250');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);
    }    
}
