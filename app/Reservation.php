<?php


namespace App;


use App\Billing\PaymentGateway;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class Reservation
{
    private Collection $tickets;
    private string $email;
    public function __construct(Collection $tickets, string $email)
    {
        $this->tickets = $tickets;
        $this->email = $email;
    }

    public function totalCost(): int
    {
        return $this->tickets->sum('price');
    }

    public function cancel(): void
    {
        $this->tickets->each(fn (Ticket $ticket) => $ticket->release());
    }

    public function complete(PaymentGateway $paymentGateway, string $paymentToken): Order
    {
        $paymentGateway->charge($this->totalCost(), $paymentToken);
        return Order::forTickets($this->tickets(), $this->email(), $this->totalCost());

    }

    public function tickets(): Collection
    {
        return $this->tickets;
    }

    public function email(): string
    {
        return $this->email;
    }
}
