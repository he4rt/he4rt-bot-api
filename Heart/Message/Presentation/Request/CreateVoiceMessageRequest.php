<?php

namespace Heart\Message\Presentation\Request;

use Illuminate\Foundation\Http\FormRequest;

class CreateVoiceMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['provider' => $this->route('provider')]);
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:twitch,discord'],
            'provider_id' => ['required'],
            'state' => ['required', 'in:muted,unmuted,disabled']
        ];
    }
}
