<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceTaskRequest;
use App\Models\MaintenanceTask;
use App\Models\Room;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceTaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MaintenanceTask::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $tasks = MaintenanceTask::query()
            ->with(['branch:id,name', 'room:id,room_number', 'assignee:id,name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('task_number', 'like', '%'.$request->string('search').'%')
                        ->orWhereHas('room', fn ($x) => $x->where('room_number', 'like', '%'.$request->string('search').'%')),
                ),
            )
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Maintenance/Index', [
            'tasks' => $tasks,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', MaintenanceTask::class);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);

        return Inertia::render('Maintenance/Create', [
            'rooms' => $rooms,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(MaintenanceTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', MaintenanceTask::class);

        $data = $request->validated();
        $data['task_number'] = MaintenanceTask::nextTaskNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['requested_by'] = $request->user()->id;
        $data['status'] = 'pending';

        MaintenanceTask::create($data);

        return redirect()->route('maintenance.index')->with('success', 'Maintenance task created.');
    }

    public function edit(MaintenanceTask $maintenanceTask): Response
    {
        $this->authorize('update', $maintenanceTask);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);

        return Inertia::render('Maintenance/Edit', [
            'task' => $maintenanceTask,
            'rooms' => $rooms,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(MaintenanceTaskRequest $request, MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $this->authorize('update', $maintenanceTask);

        $maintenanceTask->update($request->validated());

        return redirect()->route('maintenance.index')->with('success', 'Maintenance task updated.');
    }

    public function complete(MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $this->authorize('update', $maintenanceTask);

        $maintenanceTask->forceFill([
            'status' => 'completed',
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ])->save();

        return redirect()->route('maintenance.index')->with('success', 'Task marked complete.');
    }

    public function destroy(MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $this->authorize('delete', $maintenanceTask);

        $maintenanceTask->delete();

        return redirect()->route('maintenance.index')->with('success', 'Task deleted.');
    }
}
