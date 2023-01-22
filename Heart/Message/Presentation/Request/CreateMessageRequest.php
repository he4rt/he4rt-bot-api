<?php

namespace Heart\Message\Presentation\Request;

use Illuminate\Foundation\Http\FormRequest;

class CreateMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $urlPieces = explode('/', $this->url());
        $provider = array_pop($urlPieces);

        $this->merge(['provider' => $provider]);
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:twitch,discord'],
            'provider_id' => ['required'],
            'provider_message_id' => ['required'],
            'channel_id' => ['required'],
            'content' => ['required', 'string'],
            'sent_at' => ['required', 'date']
        ];
    }
}
