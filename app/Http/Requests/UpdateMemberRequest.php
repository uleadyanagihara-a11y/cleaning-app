<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends StoreMemberRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $member = $this->route('member');
        $existingRoleIds = $member instanceof Member
            ? $member->availableCleaningRoles()->pluck('cleaning_roles.id')
            : collect();

        $rules['cleaning_role_ids.*'] = [
            'integer',
            'distinct',
            Rule::exists('cleaning_roles', 'id')
                ->where(fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->when(
                        $existingRoleIds->isNotEmpty(),
                        fn (Builder $query) => $query
                            ->orWhereIn('id', $existingRoleIds),
                    )),
        ];

        return $rules;
    }
}
