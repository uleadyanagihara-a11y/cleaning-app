<?php

namespace Tests\Feature;

use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use App\Models\Member;
use App\Models\User;
use App\Services\CleaningAssignmentPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PdfOutputTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_pdf_endpoints(): void
    {
        $this->get(route('pdf.index'))
            ->assertRedirect(route('login'));
        $this->get(route('pdf.preview', ['date' => '2026-08-27']))
            ->assertRedirect(route('login'));
        $this->get(route('pdf.download', ['date' => '2026-08-27']))
            ->assertRedirect(route('login'));
    }

    public function test_unverified_users_cannot_access_pdf_endpoints(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('pdf.index'))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($user)
            ->get(route('pdf.preview', ['date' => '2026-08-27']))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_users_can_view_pdf_output_status(): void
    {
        $user = User::factory()->create();
        $this->createAssignment('2026-08-27', $user);

        $this->actingAs($user)
            ->get(route('pdf.index', ['date' => '2026-08-27']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pdf/Index')
                ->where('selectedDate', '2026-08-27')
                ->where('assignmentCount', 1));
    }

    public function test_pdf_data_includes_active_roles_and_assigned_inactive_roles(): void
    {
        $user = User::factory()->create();
        $assignedRole = CleaningRole::create([
            'name' => '玄関',
            'required_member_count' => 2,
            'is_active' => false,
        ]);
        CleaningRole::create([
            'name' => '廊下',
            'required_member_count' => 1,
        ]);
        CleaningRole::create([
            'name' => '対象外',
            'required_member_count' => 1,
            'is_active' => false,
        ]);
        $member = Member::create([
            'name' => '田中',
            'is_active' => false,
        ]);
        CleaningAssignment::create([
            'member_id' => $member->id,
            'cleaning_role_id' => $assignedRole->id,
            'assignment_date' => '2026-08-27',
            'created_by' => $user->id,
        ]);

        $data = app(CleaningAssignmentPdfService::class)->build('2026-08-27');

        $this->assertSame(['玄関', '廊下'], array_column($data['roles'], 'name'));
        $this->assertSame(['田中'], $data['roles'][0]['member_names']);
        $this->assertSame([], $data['roles'][1]['member_names']);
        $this->assertSame(3, $data['required_member_count']);
        $this->assertSame(1, $data['assigned_member_count']);
        $this->assertSame(2, $data['shortage_count']);
    }

    public function test_pdf_date_must_be_valid(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('pdf.index'))
            ->get(route('pdf.preview', ['date' => 'invalid']))
            ->assertRedirect(route('pdf.index'))
            ->assertSessionHasErrors('date');
    }

    public function test_a_date_without_assignments_cannot_be_output(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('pdf.index', ['date' => '2026-08-27']))
            ->get(route('pdf.download', ['date' => '2026-08-27']))
            ->assertRedirect(route('pdf.index', ['date' => '2026-08-27']))
            ->assertSessionHasErrors('date');
    }

    public function test_verified_users_can_preview_a_pdf(): void
    {
        $user = User::factory()->create();
        $this->createAssignment('2026-08-27', $user);

        $response = $this->actingAs($user)
            ->get(route('pdf.preview', ['date' => '2026-08-27']));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'inline');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_verified_users_can_download_a_pdf(): void
    {
        $user = User::factory()->create();
        $this->createAssignment('2026-08-27', $user);

        $response = $this->actingAs($user)
            ->get(route('pdf.download', ['date' => '2026-08-27']));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('cleaning-assignments-2026-08-27.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    private function createAssignment(string $date, User $user): CleaningAssignment
    {
        $role = CleaningRole::create([
            'name' => '玄関',
            'required_member_count' => 2,
        ]);
        $member = Member::create(['name' => '田中']);

        return CleaningAssignment::create([
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => $date,
            'created_by' => $user->id,
        ]);
    }
}
