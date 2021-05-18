<?php


namespace Tests\Unit;


use App\Exceptions\NotEnoughTicketsException;
use App\Http\Resources\ConcertResource;
use App\Models\Concert;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function can_get_formatted_date()
    {
        // Create a concert with a known date
        $concert = Concert::factory()->make([
            'date' => Carbon::parse('2016-12-01 8:00pm'),
        ]);

        // Retrieve the formatted date
        $date = $concert->formatted_date;

        // Verify the date is formatted as expected
        $this->assertEquals('December 1, 2016', $date);
    }

    /** @test */
    function can_get_formatted_start_time()
    {
        $concert = Concert::factory()->make([
            'date' => Carbon::parse('2016-12-01 17:00'),
        ]);

        $this->assertEquals('5:00pm', $concert->formatted_start_time);
    }

    /** @test */
    function can_get_ticket_price_in_dollars()
    {
        $concert = Concert::factory()->make([
            'ticket_price' => 6750,
        ]);

        $this->assertEquals('67.50', $concert->ticket_price_in_dollars);
    }

    /** @test */
    function concerts_with_a_published_at_date_are_published()
    {
        $publishedConcertA = Concert::factory()->create([
            'published_at' => Carbon::parse('-1 week')->setMilli(0),
        ]);
        $publishedConcertB = Concert::factory()->create([
            'published_at' => Carbon::parse('-1 week')->setMilli(0),
        ]);
        $unpublishedConcert = Concert::factory()->create([
            'published_at' => null,
        ]);

        $publishedConcerts = Concert::published()->get();
        $this->assertTrue($publishedConcerts->contains($publishedConcertA));
        $this->assertTrue($publishedConcerts->contains($publishedConcertB));
        $this->assertFalse($publishedConcerts->contains($unpublishedConcert));
    }

    /** @test */
    function can_order_concert_ticket()
    {
        $concert = Concert::factory()->published()->create();
        $concert->addTickets(3);

        $order = $concert->orderTickets('jane@example.com', 3);

        self::assertEquals('jane@example.com', $order->email);
        self::assertEquals(3, $order->tickets()->count());
    }

    /** @test */
    public function can_add_tickets(): void
    {
        $concert = Concert::factory()->create();
        $concert->addTickets(50);
        self::assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function tickets_remaining_does_not_include_tickets_associated_with_an_order(): void
    {
        $concert = Concert::factory()->create();
        $concert->addTickets(50);
        $concert->orderTickets('test@example.com', 20);
        self::assertEquals(30, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_order_tickets_if_not_enough_remain(): void
    {
        $concert = Concert::factory()->create();
        $concert->addTickets(10);

        try {
            $concert->orderTickets('test@example.com', 11);
        } catch (NotEnoughTicketsException $e) {
            $order = $concert->orders()->where('email', 'test@example.com')->first();
            self::assertNull($order);
            self::assertEquals(10, $concert->ticketsRemaining());
            return;
        }

        self::fail('Order succeeded even though there were not enough tickets');
    }

    /** @test */
    public function cannot_order_tickets_that_have_already_been_purchased(): void
    {
        $concert = Concert::factory()->create();
        $concert->addTickets(10);

        $concert->orderTickets('jane@example.com', 8);

        try {
            $concert->orderTickets('john@example.com', 3);
        } catch (NotEnoughTicketsException $e) {
            $johnsOrder = $concert->orders()->where('email', 'john@example.com')->first();
            self::assertNull($johnsOrder);
            self::assertEquals(2, $concert->ticketsRemaining());
            return;
        }
    }

    /** @test */
    public function can_reserve_available_tickets(): void
    {
        /** @var Concert $concert */
        $concert = Concert::factory()->published()->create()->addTickets(3);
        self::assertEquals(3, $concert->ticketsRemaining());

        $reservation = $concert->reserveTickets(2, 'john@example.com');

        self::assertCount(2, $reservation->tickets());
        self::assertEquals('john@example.com', $reservation->email());
        self::assertEquals(1, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_reserve_tickets_that_have_already_been_purchased(): void
    {
        /** @var Concert $concert */
        $concert = Concert::factory()->published()->create()->addTickets(3);
        self::assertEquals(3, $concert->ticketsRemaining());

        $concert->orderTickets('jane@example.com', 2);

        try {
            $concert->reserveTickets(2, 'jane@example.com');
        } catch (NotEnoughTicketsException $e) {
            self::assertEquals(1, $concert->ticketsRemaining());
            return;
        }

        self::fail('Failed to reserve tickets.');
    }

    /** @test */
    public function cannot_reserve_tickets_that_have_already_been_reserved(): void
    {
        /** @var Concert $concert */
        $concert = Concert::factory()->published()->create()->addTickets(3);
        self::assertEquals(3, $concert->ticketsRemaining());

        $concert->reserveTickets( 2, 'jane@example.com');

        try {
            $concert->reserveTickets( 2, 'john@example.com');
        } catch (NotEnoughTicketsException $e) {
            self::assertEquals(1, $concert->ticketsRemaining());
            return;
        }

        self::fail('Failed to reserve tickets.');
    }
}
