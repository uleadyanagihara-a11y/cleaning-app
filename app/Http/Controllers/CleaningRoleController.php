<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCleaningRoleRequest;
use App\Models\CleaningRole;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CleaningRoleController extends Controller
{
    /**
     * Display a listing of the cleaning roles.
     */
    public function index(): Response
    {
        return Inertia::render('CleaningItems/Index', [
            'cleaningRoles' => CleaningRole::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'description',
                    'required_member_count',
                    'is_active',
                ]),
        ]);
    }

    /**
     * Store a newly created cleaning role.
     */
    public function store(
        StoreCleaningRoleRequest $request,
    ): RedirectResponse {
        $validated = $request->validated();

        CleaningRole::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'required_member_count' => $validated['required_member_count'],
            'is_active' => true,
        ]);

        Inertia::flash('success', '役割を登録しました。');

        return to_route('cleaning-items.index');
    }
}
