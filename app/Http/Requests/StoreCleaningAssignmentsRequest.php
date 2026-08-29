<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreCleaningAssignmentsRequest extends PreviewCleaningAssignmentsRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.member_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('members', 'id')->where('is_active', true),
            ],
            'assignments.*.cleaning_role_id' => [
                'required',
                'integer',
                Rule::exists('cleaning_roles', 'id')->where('is_active', true),
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
            ...parent::messages(),
            'assignments.required' => '保存する掃除当番がありません。',
            'assignments.array' => '掃除当番の指定が正しくありません。',
            'assignments.min' => '保存する掃除当番がありません。',
            'assignments.*.member_id.required' => '担当メンバーを指定してください。',
            'assignments.*.member_id.integer' => '担当メンバーの指定が正しくありません。',
            'assignments.*.member_id.distinct' => '同じメンバーを複数の役割に割り当てることはできません。',
            'assignments.*.member_id.exists' => '選択できない担当メンバーが含まれています。',
            'assignments.*.cleaning_role_id.required' => '掃除役割を指定してください。',
            'assignments.*.cleaning_role_id.integer' => '掃除役割の指定が正しくありません。',
            'assignments.*.cleaning_role_id.exists' => '選択できない掃除役割が含まれています。',
        ];
    }
}
