<?php

namespace App\Billing;

use Stripe\Charge;

class StripePaymentGateway implements PaymentGateway
{
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function charge($amount, $token)
    {
        Charge::create([
            "amount"      => $amount,
            "currency"    => "usd",
            "source"      => $token,
            "description" => null,
        ], ['api_key' => $this->apiKey]);
    }

    public function totalCharges()
    {
        return 2500;
    }
}