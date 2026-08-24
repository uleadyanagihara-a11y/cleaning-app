<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
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
        $notes = $this->input('notes');

        if (is_string($notes)) {
            $notes = trim($notes);
            $notes = $notes === '' ? null : $notes;
        }

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'notes' => $notes,
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
            'name' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'cleaning_role_ids' => ['nullable', 'array'],
            'cleaning_role_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('cleaning_roles', 'id')
                    ->where('is_active', true),
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
            'name.required' => 'メンバー名を入力してください。',
            'name.string' => 'メンバー名は文字列で入力してください。',
            'name.max' => 'メンバー名は100文字以内で入力してください。',
            'notes.string' => '備考は文字列で入力してください。',
            'notes.max' => '備考は2000文字以内で入力してください。',
            'is_active.required' => '状態を選択してください。',
            'is_active.boolean' => '状態の指定が正しくありません。',
            'cleaning_role_ids.array' => '担当可能な掃除の指定が正しくありません。',
            'cleaning_role_ids.*.integer' => '担当可能な掃除の指定が正しくありません。',
            'cleaning_role_ids.*.distinct' => '同じ掃除内容が重複しています。',
            'cleaning_role_ids.*.exists' => '選択できない掃除内容が含まれています。',
        ];
    }
}
