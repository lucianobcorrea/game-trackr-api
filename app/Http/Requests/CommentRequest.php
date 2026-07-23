<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => 'required|string|min:1|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'comment.required' => 'Comment is required',
            'comment.string' => 'Comment must be a string',
            'comment.min' => 'Comment must be at least 1 character long',
            'comment.max' => 'Comment must be at most 1000 characters long',
        ];
    }
}
