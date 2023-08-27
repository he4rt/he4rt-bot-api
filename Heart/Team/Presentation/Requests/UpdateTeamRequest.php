<?php

namespace Heart\Team\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leader_id' => 'exists:users,id',
            'name' => ['string'],
            'description' => ['string'],
            'logo_url' => ['url'],
            'slug' => ['string'],
        ];
    }
}
