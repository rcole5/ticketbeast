<?php

namespace Tests\Feature;

use App\Models\Concert;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewConcertListingTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function user_can_view_a_published_concert_listing()
    {
        // Arrange
        // Create a concert
        $concert = Concert::factory()->published()->create([
            'title' => 'The red chodrd',
            'subtitle' => 'This is a subtitle',
            'date' => Carbon::parse('December 13, 2021 8:00pm'),
            'ticket_price' => 3250,
            'venue' => 'The MOsh Pit',
            'venue_address' => '123 example lane',
            'city' => 'laraville',
            'state' => 'ON',
            'zip' => '17916',
            'additional_information' => 'For tickets call 0500 000 000',
        ]);

        // Act
        // View the concert listing
        $response = $this->get('/api/concerts/' . $concert->id);

        // Assert
        // See the concert details
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $concert->id,
            'title' => 'The red chodrd',
            'subtitle' => 'This is a subtitle',
            'date' => Carbon::parse('December 13, 2021 8:00pm'),
            'ticket_price' => 3250,
            'venue' => 'The MOsh Pit',
            'venue_address' => '123 example lane',
            'city' => 'laraville',
            'state' => 'ON',
            'zip' => '17916',
            'additional_information' => 'For tickets call 0500 000 000',
        ]);
    }

    /** @test */
    function user_cannot_view_unpublished_concert_listings()
    {
        $concert = Concert::factory()->unpublished()->create();

        $response = $this->get('/api/concerts/' . $concert->id)
            ->assertStatus(404);
    }
}
