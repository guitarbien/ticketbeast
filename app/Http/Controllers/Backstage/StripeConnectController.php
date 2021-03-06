<?php

namespace App\Http\Controllers\Backstage;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Class StripeConnectController
 * @package App\Http\Controllers\Backstage
 */
class StripeConnectController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function connect()
    {
        return view('backstage.stripe-connect.connect');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function authorizeRedirect()
    {
        $url = vsprintf('%s?%s', [
            'https://connect.stripe.com/oauth/authorize',
            http_build_query([
                'response_type' => 'code',
                'scope'         => 'read_write',
                'client_id'     => config('services.stripe.client_id'),
            ]),
        ]);

        return redirect($url);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        $accessTokenResponse = Http::withHeaders([
            'Content-type' => "application/x-www-form-urlencoded",
        ])->post('https://connect.stripe.com/oauth/token', [
            'grant_type'    => 'authorization_code',
            'code'          => request('code'),
            'client_secret' => config('services.stripe.secret'),
        ])->json();

        Auth::user()->update([
            'stripe_account_id'   => $accessTokenResponse['stripe_user_id'],
            'stripe_access_token' => $accessTokenResponse['access_token'],
        ]);

        return redirect()->route('backstage.concerts.index');
    }
}
