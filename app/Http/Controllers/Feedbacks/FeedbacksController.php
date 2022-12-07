<?php

namespace App\Http\Controllers\Feedbacks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FeedbacksController extends Controller
{
    public function create(Request $request)
    {
        $this->validate($request, [
            'sender_id' => [
                'required',
                'unique:users,discord_id',
                'numeric',
                'different:target_id',
            ],
            'target_id' => [
                'required',
                'unique:users,discord_id',
                'numeric',
                'different:sender_id',
            ],
            'message' => [
                'string',
                'max:4000'
            ],
            'type' => 'string',
        ]);


    }
}
