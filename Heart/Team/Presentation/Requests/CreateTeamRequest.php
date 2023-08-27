<?php

namespace Heart\Team\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leader_id' => [
                'required', 'exists:users,id', 'unique:teams,leader_id',
            ],
            'name' => ['required'],
            'description' => ['required'],
            'logo_url' => ['required', 'url'],
        ];
    }
}
