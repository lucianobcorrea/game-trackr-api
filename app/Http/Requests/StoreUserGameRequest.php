<?php

namespace App\Http\Requests;

use App\Models\UserGame;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserGameRequest extends FormRequest
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
            'igdb_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'string', Rule::in(UserGame::STATUSES)],
            'hours_played' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'is_favorite' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'igdb_id.required' => 'The game IGDB ID is required.',
            'name.required' => 'The game name is required.',
            'slug.required' => 'The game slug is required.',
            'status.required' => 'The game status is required.',
            'status.in' => 'The selected status is invalid. Valid statuses: '.implode(', ', UserGame::STATUSES).'.',
            'hours_played.min' => 'Hours played cannot be negative.',
        ];
    }
}
