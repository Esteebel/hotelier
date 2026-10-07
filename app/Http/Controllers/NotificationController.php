<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Notification::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $notifications = Notification::query()
            ->with(['branch:id,name', 'user:id,first_name,last_name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('notification_number', 'like', '%'.$request->string('search').'%')
                        ->orWhere('title', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Notification::class);

        return Inertia::render('Notifications/Create', [
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(NotificationRequest $request): RedirectResponse
    {
        $this->authorize('create', Notification::class);

        $data = $request->validated();
        $data['notification_number'] = Notification::nextNotificationNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['status'] = 'unread';

        Notification::create($data);

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Notification created successfully.');
    }

    public function edit(Notification $notification): Response
    {
        $this->authorize('update', $notification);

        return Inertia::render('Notifications/Edit', [
            'notification' => $notification,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(NotificationRequest $request, Notification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $notification->update($request->validated());

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Notification updated successfully.');
    }

    public function complete(Notification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $notification->forceFill([
            'status' => 'read',
        ])->save();

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Notification marked as read.');
    }

    public function destroy(Notification $notification): RedirectResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Notification deleted successfully.');
    }
}
