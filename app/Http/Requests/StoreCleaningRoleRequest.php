<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCleaningRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $description = $this->input('description');

        if (is_string($description)) {
            $description = trim($description);
            $description = $description === '' ? null : $description;
        }

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'description' => $description,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cleaning_roles', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'required_member_count' => [
                'required',
                'integer',
                'min:1',
                'max:99',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '役割名を入力してください。',
            'name.string' => '役割名は文字列で入力してください。',
            'name.max' => '役割名は100文字以内で入力してください。',
            'name.unique' => '同じ役割名が既に登録されています。',
            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は2000文字以内で入力してください。',
            'required_member_count.required' => '必要人数を入力してください。',
            'required_member_count.integer' => '必要人数は整数で入力してください。',
            'required_member_count.min' => '必要人数は1人以上で入力してください。',
            'required_member_count.max' => '必要人数は99人以下で入力してください。',
        ];
    }
}
