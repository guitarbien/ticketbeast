<?php

namespace App\Billing;

use Stripe\Charge;
use Stripe\Error\InvalidRequest;

class StripePaymentGateway implements PaymentGateway
{
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function charge($amount, $token)
    {
        try {
            Charge::create([
                "amount"      => $amount,
                "currency"    => "usd",
                "source"      => $token,
                "description" => null,
            ], ['api_key' => $this->apiKey]);
        } catch (InvalidRequest $e) {
            throw new PaymentFailedException;
        }
    }

    public function getValidTestToken()
    {
        return \Stripe\Token::create([
            "card" => [
                // give fake card info
                "number"    => "4242424242424242",
                "exp_month" => 1,
                "exp_year"  => date('Y') + 1,
                "cvc"       => "123",
            ]
        ], ['api_key' => $this->apiKey])->id;
    }

    public function newChargesDuring($callback)
    {
        $lastCharge = $this->lastCharge();

        $callback($this);

        return $this->newChargesSince($lastCharge)->pluck('amount');
    }

    private function lastCharge()
    {
        return array_first(\Stripe\Charge::all(
            ["limit" => 1],
            ['api_key' => $this->apiKey]
        )->data);
    }

    private function newChargesSince($charge = null)
    {
        $newCharges = \Stripe\Charge::all(
            [
                "ending_before" => $charge ? $charge->id : null,
            ],
            ['api_key' => $this->apiKey]
        )->data;

        return collect($newCharges);
    }
}