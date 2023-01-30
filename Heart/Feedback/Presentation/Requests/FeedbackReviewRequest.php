<?php

namespace Heart\Feedback\Presentation\Requests;

use Heart\Feedback\Domain\Enums\ReviewTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class FeedbackReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['action' => $this->route('action')]);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:'.implode(',', ReviewTypeEnum::getTypes())],
            'staff_id' => ['required'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
