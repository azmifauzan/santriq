<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        /** @var Request $request */
        /** @var User $user */
        $user = $request->user();

        // Fortify's own verification.verify route has no domain constraint (it
        // matches whatever host the emailed link was clicked on — usually the
        // apex, since that's where registration happened), and its default
        // response redirects to the fixed path config('fortify.home'), which
        // only exists under the tenant subdomain route group. Build the
        // dashboard URL from the user's own tenant instead, same pattern as
        // LoginResponse and RegisterResponse.
        return redirect()->intended(
            route('dashboard', ['subdomain' => $user->tenant->subdomain])
        );
    }
}
