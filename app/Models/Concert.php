<?php

namespace App\Models;

use App\Exceptions\NotEnoughTicketsException;
use App\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

/**
 * Class Concert
 * @package App\Models
 * @mixin Builder
 *
 * @property int $id
 *
 * @property int $ticket_price
 *
 * @property string $formatted_date
 * @property string $formatted_start_time
 *
 * @property Carbon $date
 */
class Concert extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $dates = ['date', 'published_at'];

    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('F j, Y');
    }

    public function getFormattedStartTimeAttribute(): string
    {
        return $this->date->format('g:ia');
    }

    #[Pure] public function getTicketPriceInDollarsAttribute(): string
    {
        return number_format($this->ticket_price / 100, 2);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'tickets');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function orderTickets(string $email, int $amount): Order
    {
        $tickets = $this->findTickets($amount);
        return $this->createOrder($email, $tickets);
    }

    public function createOrder(string $email, Collection $tickets): Order
    {
        return Order::forTickets($tickets, $email, $tickets->sum('price'));
    }

    public function reserveTickets(int $quantity, string $email): Reservation
    {
        $tickets = $this->findTickets($quantity);
        $tickets->each(fn (Ticket $ticket) => $ticket->reserve());
        return new Reservation($tickets, $email);
    }

    public function findTickets(int $quantity): Collection
    {
        $tickets = $this->tickets()->available()->take($quantity)->get();

        if ($tickets->count() < $quantity) {
            throw new NotEnoughTicketsException();
        }

        return $tickets;
    }

    public function addTickets(int $quantity): self
    {
        foreach (range(1, $quantity) as $i) {
            $this->tickets()->create([]);
        }

        return $this;
    }

    public function ticketsRemaining()
    {
        return $this->tickets()->available()->count();
    }

    public function hasOrderFor(string $email): bool
    {
        return $this->orders()->where('email', $email)->exists();
    }

    public function ordersFor(string $email): Collection
    {
        return $this->orders()->where('email', $email)->get();
    }
}
