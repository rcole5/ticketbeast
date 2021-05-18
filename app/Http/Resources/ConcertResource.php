<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConcertResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'venue' => $this->venue,
            'date' => $this->date,
            'formatted_data' => $this->formatted_date,
            'formatted_start_time' => $this->formatted_start_time,
            'ticket_price' => (int) $this->ticket_price,
            'venue_address' => $this->venue_address,
            'state' => $this->state,
            'city' => $this->city,
            'zip' => $this->zip,
            'additional_information' => $this->additional_information,
            'published_at' => $this->published_at
        ];
    }
}
