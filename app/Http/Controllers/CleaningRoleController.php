<?php

namespace App\Http\Controllers;

use App\Models\CleaningRole;
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
                ->get(['id', 'name', 'description', 'is_active']),
        ]);
    }
}
