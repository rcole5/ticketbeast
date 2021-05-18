<?php


namespace Tests\Unit;


use App\Billing\FakePaymentGateway;
use App\Models\Concert;
use App\Models\Ticket;
use App\Reservation;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function calculating_the_total_cost(): void
    {
        $tickets = collect([
            (object)['price' => 1200],
            (object)['price' => 1200],
            (object)['price' => 1200],
        ]);
        $reservation = new reservation($tickets, 'john@example.com ');

        self::assertequals(3600, $reservation->totalcost());
    }

    /** @test */
    public function reserved_tickets_are_released_when_a_reservation_is_cancelled(): void
    {
        $tickets = Collection::times(
            3,
            fn() => Mockery::spy(Ticket::class)
        );

        $reservation = new reservation($tickets, 'john@example.com');

        $reservation->cancel();

        $tickets->each(
            fn(Mockery\MockInterface $ticket) => $ticket->shouldHaveReceived('release')
        );
    }

    /** @test */
    public function can_get_tickets_from_reservation(): void
    {
        $tickets = collect([
            (object)['price' => 1200],
            (object)['price' => 1200],
            (object)['price' => 1200],
        ]);
        $reservation = new reservation($tickets, 'john@example.com');

        self::assertEquals($tickets, $reservation->tickets());
    }

    /** @test */
    public function can_get_email_from_reservation(): void
    {
        $reservation = new reservation(collect([]), 'john@example.com');

        self::assertEquals('john@example.com', $reservation->email(),);
    }

    /** @test */
    public function completing_a_reservation(): void
    {
        $concert = Concert::factory()->published()->create([ 'ticket_price' => 1200 ]);
        $tickets = Ticket::factory(3)->create([ 'concert_id' => $concert->id ]);
        $reservation = new Reservation($tickets, 'john@example.com');
        $paymentGateway = new FakePaymentGateway;

        $order = $reservation->complete($paymentGateway, $paymentGateway->getValidTestToken());

        self::assertEquals('john@example.com', $order->email);
        self::assertEquals(3, $order->ticketQuantity());
        self::assertEquals(3 * 1200, $order->amount);
        self::assertEquals(3 * 1200, $paymentGateway->totalCharges());
    }
}
