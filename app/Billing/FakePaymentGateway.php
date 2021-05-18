<?php


namespace App\Billing;


use Closure;
use Illuminate\Support\Collection;

class FakePaymentGateway implements PaymentGateway
{
    private Collection $charges;
    private ?Closure $beforeFirstChargeCallback;

    public function __construct()
    {
        $this->charges = collect();
        $this->beforeFirstChargeCallback = null;
    }

    function getValidTestToken(): string
    {
        return "valid-token";
    }

    public function charge(int $amount, string $token)
    {
        if ($this->beforeFirstChargeCallback !== null) {
            try {
                $callback = $this->beforeFirstChargeCallback;
                $this->beforeFirstChargeCallback = null;
                $callback($this);
            } catch (\Throwable $e) {
                dd($e->getMessage());
            }
        }
        if ($token !== $this->getValidTestToken()) {
            throw new PaymentFailedException();
        }
        $this->charges[] = $amount;
    }

    public function totalCharges()
    {
        return $this->charges->sum();
    }

    public function beforeFirstCharge(Closure $callback)
    {
        $this->beforeFirstChargeCallback = $callback;
    }
}
