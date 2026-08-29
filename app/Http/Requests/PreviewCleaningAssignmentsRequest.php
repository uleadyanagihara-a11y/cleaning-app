<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewCleaningAssignmentsRequest extends FormRequest
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
            'assignment_date' => ['required', 'date_format:Y-m-d'],
            'excluded_member_ids' => ['nullable', 'array'],
            'excluded_member_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('members', 'id')->where('is_active', true),
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
            'assignment_date.required' => '対象日を入力してください。',
            'assignment_date.date_format' => '対象日は年-月-日の形式で入力してください。',
            'excluded_member_ids.array' => '除外メンバーの指定が正しくありません。',
            'excluded_member_ids.*.integer' => '除外メンバーの指定が正しくありません。',
            'excluded_member_ids.*.distinct' => '同じ除外メンバーが重複しています。',
            'excluded_member_ids.*.exists' => '選択できない除外メンバーが含まれています。',
        ];
    }
}
