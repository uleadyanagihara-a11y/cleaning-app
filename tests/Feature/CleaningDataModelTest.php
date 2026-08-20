<?php

namespace Tests\Feature;

use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CleaningDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaning_tables_have_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('members', [
            'id',
            'name',
            'notes',
            'is_active',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('cleaning_roles', [
            'id',
            'name',
            'description',
            'is_active',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('cleaning_assignments', [
            'id',
            'member_id',
            'cleaning_role_id',
            'assignment_date',
            'notes',
            'created_by',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_cleaning_assignment_relations_can_be_loaded(): void
    {
        $user = User::factory()->create();
        $member = Member::create(['name' => '田中', 'is_active' => true]);
        $role = CleaningRole::create(['name' => '玄関', 'is_active' => true]);

        $assignment = CleaningAssignment::create([
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => '2026-08-13',
            'created_by' => $user->id,
        ]);

        $this->assertTrue($assignment->member->is($member));
        $this->assertTrue($assignment->cleaningRole->is($role));
        $this->assertTrue($assignment->creator->is($user));
        $this->assertTrue($member->cleaningAssignments->contains($assignment));
        $this->assertTrue($role->cleaningAssignments->contains($assignment));
        $this->assertTrue($user->createdCleaningAssignments->contains($assignment));
        $this->assertSame('2026-08-13', $assignment->assignment_date->toDateString());
    }

    public function test_deleting_creator_keeps_assignment_and_nulls_created_by(): void
    {
        $assignment = $this->createAssignment();

        $assignment->creator->delete();

        $this->assertNull($assignment->refresh()->created_by);
        $this->assertDatabaseHas('cleaning_assignments', ['id' => $assignment->id]);
    }

    public function test_member_with_an_assignment_cannot_be_deleted(): void
    {
        $assignment = $this->createAssignment();

        $this->expectException(QueryException::class);

        $assignment->member->delete();
    }

    public function test_cleaning_role_with_an_assignment_cannot_be_deleted(): void
    {
        $assignment = $this->createAssignment();

        $this->expectException(QueryException::class);

        $assignment->cleaningRole->delete();
    }

    public function test_same_member_role_and_date_cannot_be_registered_twice(): void
    {
        $assignment = $this->createAssignment();

        $this->expectException(QueryException::class);

        CleaningAssignment::create([
            'member_id' => $assignment->member_id,
            'cleaning_role_id' => $assignment->cleaning_role_id,
            'assignment_date' => $assignment->assignment_date,
            'created_by' => $assignment->created_by,
        ]);
    }

    private function createAssignment(): CleaningAssignment
    {
        $user = User::factory()->create();
        $member = Member::create(['name' => '田中']);
        $role = CleaningRole::create(['name' => '玄関']);

        return CleaningAssignment::create([
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => '2026-08-13',
            'created_by' => $user->id,
        ]);
    }
}
