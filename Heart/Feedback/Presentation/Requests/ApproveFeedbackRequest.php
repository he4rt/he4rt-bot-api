<?php

namespace Heart\Feedback\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'numeric',],
        ];
    }
}
