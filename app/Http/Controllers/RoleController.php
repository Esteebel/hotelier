<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeView($request);

        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'),
            )
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Role $role) => $this->serialize($role));

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeManage($request);

        return Inertia::render('Roles/Create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $role = Role::create(['name' => $request->string('name')->toString()]);
        $role->syncPermissions($request->input('permissions', []));

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Request $request, Role $role): Response
    {
        $this->authorizeManage($request);

        return Inertia::render('Roles/Edit', [
            'role' => $role->only(['id', 'name', 'guard_name']),
            'permissionGroups' => $this->permissionGroups(),
            'permissions' => $role->permissions()->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorizeManage($request);

        $role->update(['name' => $request->string('name')->toString()]);
        $role->syncPermissions($request->input('permissions', []));

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeManage($request);

        if ($role->name === 'super-admin') {
            abort(403);
        }

        if ($role->users()->count() > 0) {
            abort(403);
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    private function authorizeView(Request $request): void
    {
        if (! $request->user()->hasAnyPermission([
            PermissionName::ViewUsers->value,
            PermissionName::ManageUsers->value,
            PermissionName::ManageRoles->value,
        ])) {
            abort(403);
        }
    }

    private function authorizeManage(Request $request): void
    {
        if (! $request->user()->hasPermissionTo(PermissionName::ManageRoles->value)) {
            abort(403);
        }
    }

    private function permissionGroups(): array
    {
        return collect(PermissionName::grouped())
            ->map(fn (array $permissions, string $group) => [
                'label' => $group,
                'permissions' => array_map(
                    fn (PermissionName $permission) => [
                        'name' => $permission->value,
                        'label' => PermissionName::labels()[$permission->value] ?? $permission->value,
                    ],
                    $permissions,
                ),
            ])
            ->values()
            ->all();
    }

    private function serialize(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions_count' => $role->permissions_count,
            'users_count' => $role->users_count,
            'created_at' => $role->created_at?->toDateTimeString(),
        ];
    }
}