<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->user();

        return [
            'name' => 'sometimes|required|string|max:255',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'profile_color' => [
                'sometimes',
                'required',
                'string',
                'max:9',
            ],
            'visibility' => [
                'sometimes',
                'required',
                'string',
                Rule::in(User::VISIBILITY_OPTIONS),
            ],
            'bio' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
