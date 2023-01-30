<?php

namespace Heart\Character\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'redeem_code' => ['required', 'string'],
        ];
    }
}
