<?php

namespace App\Billing;

use Illuminate\Support\Arr;
use Stripe\Error\InvalidRequest;

/**
 * Class StripePaymentGateway
 * @package App\Billing
 */
class StripePaymentGateway implements PaymentGateway
{
    const TEST_CARD_NUMBER = '4242424242424242';

    /**
     * StripePaymentGateway constructor.
     * @param $apiKey
     */
    public function __construct(private $apiKey)
    {
    }

    /**
     * @param $amount
     * @param $token
     * @param string $destinationAccountId
     * @return Charge|mixed
     */
    public function charge($amount, $token, string $destinationAccountId)
    {
        try {
            $stripeCharge = \Stripe\Charge::create([
                "amount"      => $amount,
                "currency"    => "usd",
                "source"      => $token,
                "destination" => [
                    "account" => $destinationAccountId,
                    "amount"  => ($amount * .9),
                ],
            ], ['api_key' => $this->apiKey]);

            return new Charge([
                'amount'         => $stripeCharge['amount'],
                'card_last_four' => $stripeCharge['source']['last4'],
                'destination'    => $destinationAccountId,
            ]);
        } catch (InvalidRequest) {
            throw new PaymentFailedException;
        }
    }

    /**
     * @param string $cardNumber
     * @return string
     */
    public function getValidTestToken($cardNumber = self::TEST_CARD_NUMBER): string
    {
        return \Stripe\Token::create([
            "card" => [
                // give fake card info
                "number"    => $cardNumber,
                "exp_month" => 1,
                "exp_year"  => date('Y') + 1,
                "cvc"       => "123",
            ]
        ], ['api_key' => $this->apiKey])->id;
    }

    /**
     * @param $callback
     * @return \Illuminate\Support\Collection
     */
    public function newChargesDuring($callback)
    {
        $lastCharge = $this->lastCharge();

        $callback($this);

        return $this->newChargesSince($lastCharge)->map(function($stripeCharge) {
            return new Charge([
                'amount'         => $stripeCharge['amount'],
                'card_last_four' => $stripeCharge['source']['last4'],
            ]);
        });
    }

    /**
     * @return mixed
     */
    private function lastCharge()
    {
        return Arr::first(\Stripe\Charge::all(
            ["limit" => 1],
            ['api_key' => $this->apiKey]
        )->data);
    }

    /**
     * @param null $charge
     * @return \Illuminate\Support\Collection
     */
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
