<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public static function forTickets($tickets, $email, $amount)
    {
        $order = self::create([
            'email'  => $email,
            'amount' => $amount,
        ]);

        // 寫入票券
        foreach ($tickets as $ticket)
        {
            $order->tickets()->save($ticket);
        }

        return $order;
    }

    public static function findByConfirmationNumber($confirmationNumber)
    {
        return self::where('confirmation_number', $confirmationNumber)->firstOrFail();
    }

    public function concert()
    {
        return $this->belongsTo(Concert::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketQuantity()
    {
        return $this->tickets()->count();
    }

    public function toArray()
    {
        return [
            'email'           => $this->email,
            'ticket_quantity' => $this->ticketQuantity(),
            'amount'          => $this->amount,
        ];
    }
}
