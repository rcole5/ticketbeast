<?php

namespace App\Models;

use App\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Class Order
 * @package App\Models
 * @mixin Builder
 */
class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function forTickets(Collection $tickets, string $email, int $amount): self
    {
        $order = self::create([
            'email' => $email,
            'amount' => $amount,
        ]);

        $tickets->each(fn (Ticket $ticket) => $ticket->update([ 'order_id' => $order->id ]));

        return $order;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketQuantity(): int
    {
        return $this->tickets()->count();
    }

    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class);
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'ticket_quantity' => $this->ticketQuantity(),
            'amount' => $this->amount,
        ];
    }
}
