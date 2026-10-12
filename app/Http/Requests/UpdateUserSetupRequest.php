<?php

namespace App\Http\Requests;

use App\Models\UserSetup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUserSetupRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:10240'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $setup = $this->route('setup');
                if (is_numeric($setup) || is_string($setup)) {
                    $setup = UserSetup::find($setup);
                }

                if ($setup instanceof UserSetup) {
                    $currentCount = $setup->getMedia('images')->count();
                    $removeIds = (array) ($this->input('remove_image_ids') ?? []);
                    $removeCount = count($removeIds);
                    $newImages = (array) ($this->file('images') ?? []);
                    $newCount = count($newImages);
                    $finalCount = max(0, $currentCount - $removeCount) + $newCount;

                    if ($finalCount > UserSetup::MAX_IMAGES) {
                        $validator->errors()->add(
                            'images',
                            'A setup cannot have more than '.UserSetup::MAX_IMAGES." images in total. This update would result in {$finalCount} images."
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.max' => 'Each image must not exceed 10MB in size.',
        ];
    }
}
