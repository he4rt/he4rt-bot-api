<?php

namespace Heart\Authentication\OAuth\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Heart\Authentication\OAuth\Application\OAuthService;
use Heart\Authentication\OAuth\Domain\Actions\RedirectOAuthUrl;

class OAuthController extends Controller
{
    public function getRedirect(string $provider, RedirectOAuthUrl $action): RedirectResponse
    {
        return redirect()->to($action->handle($provider));
    }

    public function getAuthenticate(string $provider, OAuthService $action): RedirectResponse
    {
        $action->handle($provider, request()->input('code'));
        return redirect()->intended('/profile');
    }
}
