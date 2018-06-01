<?php

namespace Tests\Feature;

use App\Invitation;
use App\Facades\InvitationCode;
use App\Mail\InvitationEmail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvitePromoterTest extends TestCase
{
    use RefreshDatabase;

    public function test_使用cli邀請promoter()
    {
        Mail::fake();
        InvitationCode::shouldReceive('generate')->andReturn('TESTCODE1234');

        $this->artisan('invite-promoter', ['email' => 'john@example.com']);

        static::assertEquals(1, Invitation::count());

        $invitation = Invitation::first();
        static::assertEquals('john@example.com', $invitation->email);
        static::assertEquals('TESTCODE1234', $invitation->code);

        Mail::assertSent(InvitationEmail::class, function($mail) use($invitation) {
            return $mail->hasTo('john@example.com')
                && $mail->invitation->is($invitation);
        });
    }
}
