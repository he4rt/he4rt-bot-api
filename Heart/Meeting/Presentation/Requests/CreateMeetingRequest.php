<?php

namespace Heart\Meeting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meeting_type_id' => ['required', 'integer', 'exists:meeting_types,id'],
            'discord_id' => ['required', 'integer', 'exists:users']
        ];
    }
}
