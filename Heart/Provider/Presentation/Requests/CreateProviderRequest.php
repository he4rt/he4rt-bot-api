<?php

namespace Heart\Provider\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(['provider' => $this->route('provider')]);
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:discord,twitch'],
            'provider_id' => ['required'],
            'username' => ['required']
        ];
    }
}
