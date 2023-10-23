<?php

namespace Tests\Feature;

use App\Models\Travel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TravelListTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_travel_list_returns_paginated_data_correctly(): void
    {
        Travel::factory(16)->create(['is_public'=> true]);
        $response = $this->get('/api/v1/travel');

        $response->assertStatus(200);
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('meta.last_page', 2);
    }
    
    public function test_travel_list_sohws_only_public_records(): void
    {
        $public_travel = Travel::factory()->create(['is_public'=> true]);
        Travel::factory()->create(['is_public'=> false]);
        
        $response = $this->get('/api/v1/travel');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', $public_travel->name);
    }
}
