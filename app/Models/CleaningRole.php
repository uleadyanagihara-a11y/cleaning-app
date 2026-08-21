<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_active'])]
class CleaningRole extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function cleaningAssignments(): HasMany
    {
        return $this->hasMany(CleaningAssignment::class);
    }

    public function availableMembers(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'cleaning_role_member')
            ->withTimestamps();
    }
}
