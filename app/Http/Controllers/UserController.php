<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\HotelBranch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('branches:id,name')
            ->withCount('branches')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('email', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when(
                $request->filled('role'),
                fn ($query) => $query->role($request->string('role')->toString()),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => $this->serialize($user));

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $this->roleOptions(),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', $this->formData());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'status' => $request->string('status')->toString(),
        ]);

        $user->syncRoles($request->input('roles', []));
        $user->branches()->sync($request->input('branches', []));

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Edit', [
            'user' => $this->serialize($user),
            ...$this->formData(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin')) {
            abort(403);
        }

        $user->update([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'status' => $request->string('status')->toString(),
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => $request->string('password')->toString(),
            ]);
        }

        $user->syncRoles($request->input('roles', []));
        $user->branches()->sync($request->input('branches', []));

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->hasRole('super-admin')) {
            abort(403);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User removed successfully.');
    }

    private function formData(): array
    {
        return [
            'roles' => Role::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'name' => $role->name,
                    'label' => \Illuminate\Support\Str::headline($role->name),
                ])
                ->all(),
            'branches' => HotelBranch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (HotelBranch $branch) => ['id' => $branch->id, 'name' => $branch->name])
                ->all(),
            'permissions' => Permission::query()
                ->orderBy('name')
                ->pluck('name')
                ->all(),
        ];
    }

    private function roleOptions(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'name' => $role->name,
                'label' => \Illuminate\Support\Str::headline($role->name),
            ])
            ->values()
            ->all();
    }

    private function serialize(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'status_label' => $user->status_label,
            'created_at' => $user->created_at?->toDateTimeString(),
            'roles' => $user->getRoleNames()->sort()->values()->all(),
            'branches' => $user->branches->map(fn (HotelBranch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
            ])->all(),
            'branches_count' => $user->branches_count,
        ];
    }
}