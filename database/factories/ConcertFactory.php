<?php

namespace Database\Factories;

use App\Models\Concert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConcertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Concert::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'title' => 'Example Band',
            'subtitle' => 'with The Fake Openers',
            'date' => Carbon::parse('+2 weeks'),
            'ticket_price' => 2000,
            'venue' => 'Example Venue',
            'venue_address' => '123 example lane',
            'city' => 'Fakeville',
            'state' => 'ON',
            'zip' => '17916',
            'additional_information' => 'Some sample additional info',
        ];
    }

    public function published(): Factory
    {
        return $this->state(fn ($attributes) => [
            'published_at' => Carbon::parse('-1 week')->setMilli(0),
        ]);
    }

    public function unpublished(): Factory
    {
        return $this->state(fn ($attributes) => [
            'published_at' => null,
        ]);
    }
}
