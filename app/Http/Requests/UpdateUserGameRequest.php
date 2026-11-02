<?php

namespace App\Http\Requests;

use App\Models\UserGame;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserGameRequest extends FormRequest
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
            'status' => ['sometimes', 'required', 'string', Rule::in(UserGame::STATUSES)],
            'hours_played' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999'],
            'is_favorite' => ['sometimes', 'required', 'boolean'],
            'rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'cover_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid. Valid statuses: '.implode(', ', UserGame::STATUSES).'.',
            'hours_played.min' => 'Hours played cannot be negative.',
        ];
    }
}
