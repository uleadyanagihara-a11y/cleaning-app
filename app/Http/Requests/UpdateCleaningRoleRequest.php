<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateCleaningRoleRequest extends StoreCleaningRoleRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['name'] = [
            'required',
            'string',
            'max:100',
            Rule::unique('cleaning_roles', 'name')
                ->ignore($this->route('cleaningRole')),
        ];
        $rules['is_active'] = ['required', 'boolean'];

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'is_active.required' => '状態を選択してください。',
            'is_active.boolean' => '状態の指定が正しくありません。',
        ];
    }
}
