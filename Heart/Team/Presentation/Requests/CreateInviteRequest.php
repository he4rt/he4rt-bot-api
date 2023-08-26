<?php

namespace Heart\Team\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invited_by' => ['required', 'exists:users,id'],
            'member_id' => ['required', 'exists:users,id'],
        ];
    }
}
