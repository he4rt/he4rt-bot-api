<?php

namespace Heart\Team\Presentation\Requests;

use Heart\Team\Domain\Enums\InviteAnswerEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AnswerInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answer' => new Enum(InviteAnswerEnum::class),
        ];
    }
}
