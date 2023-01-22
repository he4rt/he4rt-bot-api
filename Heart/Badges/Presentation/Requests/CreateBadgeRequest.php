<?php

namespace Heart\Badges\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:discord,twitch'],
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'image_url' => ['required', 'url'],
            'redeem_code' => ['required', 'string'],
            'active' => ['required', 'boolean'],
        ];
    }
}
