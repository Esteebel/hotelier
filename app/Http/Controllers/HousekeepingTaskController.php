<?php

namespace App\Http\Controllers;

use App\Http\Requests\HousekeepingTaskRequest;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HousekeepingTaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', HousekeepingTask::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $tasks = HousekeepingTask::query()
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

        return Inertia::render('Housekeeping/Index', [
            'tasks' => $tasks,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', HousekeepingTask::class);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);

        return Inertia::render('Housekeeping/Create', [
            'rooms' => $rooms,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(HousekeepingTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', HousekeepingTask::class);

        $data = $request->validated();
        $data['task_number'] = HousekeepingTask::nextTaskNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['requested_by'] = $request->user()->id;
        $data['status'] = 'pending';

        HousekeepingTask::create($data);

        return redirect()->route('housekeeping.index')->with('success', 'Housekeeping task created.');
    }

    public function edit(HousekeepingTask $housekeepingTask): Response
    {
        $this->authorize('update', $housekeepingTask);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);

        return Inertia::render('Housekeeping/Edit', [
            'task' => $housekeepingTask,
            'rooms' => $rooms,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(HousekeepingTaskRequest $request, HousekeepingTask $housekeepingTask): RedirectResponse
    {
        $this->authorize('update', $housekeepingTask);

        $housekeepingTask->update($request->validated());

        return redirect()->route('housekeeping.index')->with('success', 'Housekeeping task updated.');
    }

    public function complete(HousekeepingTask $housekeepingTask): RedirectResponse
    {
        $this->authorize('update', $housekeepingTask);

        $housekeepingTask->forceFill([
            'status' => 'completed',
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ])->save();

        return redirect()->route('housekeeping.index')->with('success', 'Task marked complete.');
    }

    public function destroy(HousekeepingTask $housekeepingTask): RedirectResponse
    {
        $this->authorize('delete', $housekeepingTask);

        $housekeepingTask->delete();

        return redirect()->route('housekeeping.index')->with('success', 'Task deleted.');
    }
}
