<?php

namespace Heart\Meeting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge(['provider' => $this->route('provider')]);
    }

    public function rules(): array
    {
        return [
            'meeting_type_id' => ['required', 'integer', 'exists:meeting_types,id'],
            'provider_id' => ['required', 'exists:providers'],
            'provider' => ['required']
        ];
    }
}
