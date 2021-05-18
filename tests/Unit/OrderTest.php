<?php


namespace Tests\Unit;


use App\Models\Concert;
use App\Models\Order;
use App\Models\Ticket;
use App\Reservation;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function creating_order_from_tickets_email_and_amount(): void
    {
        $concert = Concert::factory()->create()->addTickets(5);
        self::assertEquals(5, $concert->ticketsRemaining());
        $order = Order::forTickets($concert->findTickets(3), 'john@example.com', 3 * 1200);

        self::assertEquals('john@example.com', $order->email);
        self::assertEquals(3, $order->ticketQuantity());
        self::assertEquals(3 * 1200, $order->amount);
        self::assertEquals(2, $concert->ticketsRemaining());
    }

    /** @test */
    public function converting_to_an_array(): void
    {
        $concert = Concert::factory()->create([ 'ticket_price' => 1200 ])->addTickets(10);
        $order = $concert->orderTickets('jane@example.com', 5);

        $result = $order->toArray();

        self::assertEquals([
            'email' => 'jane@example.com',
            'ticket_quantity' => 5,
            'amount' => 6000
        ], $result);
    }
}
