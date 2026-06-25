<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class ReplyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by the 'role:vendor' route middleware
        // (ownership is enforced in the controller).
        return true;
    }

    public function rules(): array
    {
        return [
            'reply' => 'required|string|max:500',
        ];
    }
}
