<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Support\ContextualBranch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()?->hasPermissionTo(PermissionName::ViewAuditLogs->value)) {
            abort(403);
        }

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $logs = AuditLog::query()
            ->with(['branch:id,name', 'user:id,name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('event', 'like', '%'.$request->string('search').'%')
                        ->orWhere('auditable_type', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when(
                $request->filled('event'),
                fn ($query) => $query->where('event', $request->string('event')),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'event', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }
}
