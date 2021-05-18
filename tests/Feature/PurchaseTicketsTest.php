<?php


namespace Tests\Feature;


use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Models\Concert;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    private FakePaymentGateway $paymentGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = new FakePaymentGateway();
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
    }

    private function orderTickets(Concert $concert, array $params): TestResponse
    {
        $requestBackup = $this->app['request'];
        $response = $this->json('POST', "/api/concerts/{$concert->id}/orders", $params);
        $this->app['request'] = $requestBackup;
        return $response;
    }

    /** @test */
    public function customer_can_purchase_published_concert_tickets(): void
    {
        /** @var Concert $concert */
        $concert = Concert::factory()->published()->create([
            'ticket_price' => 3250
        ])->addTickets(10);

        $this
            ->ordertickets($concert, [
                'email' => 'john@example.com',
                'ticket_quantity' => 3,
                'payment_token' => $this->paymentGateway->getValidTestToken(),
            ])
            ->assertstatus(201)
            ->assertJsonFragment([
                'email' => 'john@example.com',
                'ticket_quantity' => 3,
                'amount' => 9750
            ]);

        self::assertEquals(9750, $this->paymentGateway->totalCharges());

        $order = $concert->orders()->where('email', 'john@example.com')->first();
        self::assertNotNull($order);
        self::assertTrue($concert->hasOrderFor('john@example.com'));
        self::assertEquals(3, $concert->ordersFor('john@example.com')->first()->ticketQuantity());
    }

    /** @test */
    public function cannot_purchase_tickets_to_an_unpublished_concert(): void
    {
        /** @var Concert $concert */
        $concert = Concert::factory()->unpublished()->create()->addTickets(10);

        $this
            ->ordertickets($concert, [
                'email' => 'john@example.com',
                'ticket_quantity' => 3,
                'payment_token' => $this->paymentGateway->getValidTestToken(),
            ])
            ->assertStatus(404);
        self::assertEquals(0, $concert->orders()->count());
        self::assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function email_is_required_to_purchase_published_tickets(): void
    {
        $concert = Concert::factory()->published()->create()->addTickets(10);

        $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function email_must_be_valid_to_purchase_tickets_to_published_concerts(): void
    {
        $concert = Concert::factory()->published()->create()->addTickets(10);

        $this->orderTickets($concert, [
            'email' => 'invalid-email-address',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function ticket_quantity_is_required_to_purchase_tickets_to_published_concerts(): void
    {
        $concert = Concert::factory()->published()->create()->addTickets(10);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ticket_quantity');
    }

    /** @test */
    public function ticket_quantity_is_larger_than_zero_to_purchase_tickets_to_published_concerts(): void
    {
        $concert = Concert::factory()->published()->create()->addTickets(10);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ticket_quantity');
    }

    /** @test */
    public function payment_token_is_required(): void
    {
        $concert = Concert::factory()->published()->create()->addTickets(10);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_token');
    }

    /** @test */
    public function order_is_not_created_if_payment_fails(): void
    {
        /** @var Concert $concert */
        $concert = Concert::factory()->published()->create()->addTickets(10);

        $this
            ->orderTickets($concert, [
                'email' => 'john@example.com',
                'ticket_quantity' => 3,
                'payment_token' => 'invalid-payment-token',
            ])
            ->assertStatus(422);

        self::assertFalse($concert->hasOrderFor('john@example.com'));
        self::assertEquals(10, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_purchase_more_thickets_than_remain(): void
    {
        $concert = Concert::factory()->published()->create()->addTickets(50);

        $this
            ->ordertickets($concert, [
                'email' => 'john@example.com',
                'ticket_quantity' => 51,
                'payment_token' => $this->paymentGateway->getValidTestToken(),
            ])
            ->assertstatus(422);

        $order = $concert->orders()->where('email', 'john@example.com')->first();
        self::assertNull($order);
        self::assertEquals(0, $this->paymentGateway->totalCharges());
        self::assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_purchase_tickets_another_customer_is_trying_to_purchase(): void
    {
        $concert = Concert::factory()->published()->create([
            'ticket_price' => 1200
        ])->addTickets(3);

        $this->paymentGateway->beforeFirstCharge(function (PaymentGateway $paymentGateway) use ($concert) {
            $this
                ->ordertickets($concert, [
                    'email' => 'personB@example.com',
                    'ticket_quantity' => 1,
                    'payment_token' => $this->paymentGateway->getValidTestToken(),
                ])
                ->assertstatus(422);

            $order = $concert->orders()->where('email', 'personB@example.com')->first();
            self::assertNull($order);
            self::assertEquals(0, $this->paymentGateway->totalCharges());
        });

        $this
            ->ordertickets($concert, [
                'email' => 'personA@example.com',
                'ticket_quantity' => 3,
                'payment_token' => $this->paymentGateway->getValidTestToken(),
            ])
            ->assertStatus(201);


        self::assertEquals(1200 * 3, $this->paymentGateway->totalCharges());
        self::assertTrue($concert->hasOrderFor('personA@example.com'));
        self::assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketQuantity());
    }
}
