<?php

namespace Heart\User\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'info' => ['array', 'required'],
            'info.name' => ['string', 'max:100'],
            'info.nickname' => ['string', 'max:100'],
            'info.linkedin_url' => ['string'],
            'info.github_url' => ['string'],
            'info.birthdate' => ['string'],
            'info.about' => ['string'],
        ];
    }
}
