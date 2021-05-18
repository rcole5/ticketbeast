<?php


namespace Tests\Unit\Billing;


use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{
    /** @test */
    public function charges_with_a_valid_payment_token_are_successful(): void
    {
        $paymentGateway = new FakePaymentGateway;
        $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());

        $this->assertEquals(2500, $paymentGateway->totalCharges());
    }

    /** @test */
    public function charges_with_an_invalid_payment_token_fail(): void
    {
        try {
            $paymentGateway = new FakePaymentGateway;
            $paymentGateway->charge(2500, 'invalid-payment-token');
        } catch (PaymentFailedException $e) {
            self::assertIsObject($e);
            return;
        }

        self::fail('An invalid payment token should throw and exception.');
    }

    /** @test */
    public function running_a_hook_the_first_charge(): void
    {
        $paymentGateway = new FakePaymentGateway();
        $timesCallbackRan = 0;

        $paymentGateway->beforeFirstCharge(function ($paymentGateway) use (&$timesCallbackRan) {
            $timesCallbackRan++;
            $this->assertEquals(0, $paymentGateway->totalCharges());
        });

        $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
        self::assertEquals(1, $timesCallbackRan);
        self::assertEquals(2500, $paymentGateway->totalCharges());
    }
}
