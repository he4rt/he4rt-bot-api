<?php

namespace Heart\Provider\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Provider\Application\NewAccountByProvider;
use Heart\Provider\Domain\Enums\ProviderEnum;
use Heart\Provider\Presentation\Requests\CreateProviderRequest;

class ProvidersController extends Controller
{
    public function postProvider(
        CreateProviderRequest $request,
        string $provider,
        NewAccountByProvider $action,
    ) {
        $action->handle(
            ProviderEnum::from($provider),
            $request->input('provider_id'),
            $request->input('username')
        );

        return response()->noContent();
    }
}
