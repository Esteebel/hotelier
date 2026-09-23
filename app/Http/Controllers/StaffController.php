<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffRequest;
use App\Models\Staff;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Staff::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $staff = Staff::query()
            ->with(['branch:id,name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('staff_number', 'like', '%'.$request->string('search').'%')
                        ->orWhere('first_name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('last_name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('position', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->when(
                $request->filled('department'),
                fn ($query) => $query->where('department', $request->string('department')),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Staff/Index', [
            'staff' => $staff,
            'filters' => $request->only(['search', 'status', 'department', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Staff::class);

        return Inertia::render('Staff/Create', [
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $this->authorize('create', Staff::class);

        $data = $request->validated();
        $data['staff_number'] = Staff::nextStaffNumber();
        $data['branch_id'] = ContextualBranch::id();

        Staff::create($data);

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff member added successfully.');
    }

    public function edit(Staff $staff): Response
    {
        $this->authorize('update', $staff);

        return Inertia::render('Staff/Edit', [
            'staff' => $staff,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(StaffRequest $request, Staff $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $staff->update($request->validated());

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        $staff->delete();

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff member removed successfully.');
    }
}
