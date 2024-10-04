<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class EditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $key = $this->get('key');

        $validationRules = [
            'title' => 'required|string|max:50',
            'description' => 'nullable|max:255',
            'is_active' => 'required|boolean',
        ];

        if (! array_key_exists($key, $validationRules)) {
            abort(422, "Unprocessable field: $key");
        }

        return [
            'key' => 'required|string',
            'value' => $validationRules[$key],
        ];
    }
}
