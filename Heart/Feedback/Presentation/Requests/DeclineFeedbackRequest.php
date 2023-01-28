<?php

namespace Heart\Feedback\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeclineFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_id' => [
                'required',
                'numeric',
            ],
            'decline_message' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
