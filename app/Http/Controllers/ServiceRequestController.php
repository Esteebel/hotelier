<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Models\Room;
use App\Models\Guest;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceRequest::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $services = ServiceRequest::query()
            ->with(['branch:id,name', 'room:id,room_number', 'guest:id,first_name,last_name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('service_number', 'like', '%'.$request->string('search').'%')
                        ->orWhere('service_type', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ServiceRequest::class);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);
        $guests = Guest::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Services/Create', [
            'rooms' => $rooms,
            'guests' => $guests,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(ServiceRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', ServiceRequest::class);

        $data = $request->validated();
        $data['service_number'] = ServiceRequest::nextServiceNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['requested_by'] = $request->user()->id;
        $data['status'] = 'pending';

        ServiceRequest::create($data);

        return redirect()->route('services.index')->with('success', 'Service request created.');
    }

    public function edit(ServiceRequest $serviceRequest): Response
    {
        $this->authorize('update', $serviceRequest);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);
        $guests = Guest::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Services/Edit', [
            'service' => $serviceRequest,
            'rooms' => $rooms,
            'guests' => $guests,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(ServiceRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('update', $serviceRequest);

        $serviceRequest->update($request->validated());

        return redirect()->route('services.index')->with('success', 'Service request updated.');
    }

    public function complete(ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('update', $serviceRequest);

        $serviceRequest->forceFill([
            'status' => 'completed',
            'assigned_to' => auth()->id(),
            'completed_at' => now(),
        ])->save();

        return redirect()->route('services.index')->with('success', 'Service request completed.');
    }

    public function destroy(ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('delete', $serviceRequest);

        $serviceRequest->delete();

        return redirect()->route('services.index')->with('success', 'Service request deleted.');
    }
}
