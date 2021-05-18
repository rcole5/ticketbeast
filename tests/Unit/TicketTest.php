<?php


namespace Tests\Unit;


use App\Models\Concert;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_ticket_can_be_reserved(): void
    {
        $ticket = Ticket::factory()->create();
        self::assertNull($ticket->reserved_at);

        $ticket->reserve();

        self::assertNotNull($ticket->fresh()->reserved_at);
    }

    /** @test */
    public function a_ticket_can_be_released(): void
    {
        $ticket = Ticket::factory()->reserved()->create();
        self::assertNotNull($ticket->reserved_at);

        $ticket->release();

        self::assertNull($ticket->fresh()->reserved_at);
    }
}
