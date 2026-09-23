<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Expense::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $expenses = Expense::query()
            ->with(['branch:id,name', 'creator:id,first_name,last_name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('expense_number', 'like', '%'.$request->string('search').'%')
                        ->orWhere('description', 'like', '%'.$request->string('search').'%')
                        ->orWhere('category', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->when(
                $request->filled('category'),
                fn ($query) => $query->where('category', $request->string('category')),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only(['search', 'status', 'category', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Expense::class);

        return Inertia::render('Expenses/Create', [
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $data = $request->validated();
        $data['expense_number'] = Expense::nextExpenseNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'completed';

        Expense::create($data);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense): Response
    {
        $this->authorize('update', $expense);

        return Inertia::render('Expenses/Edit', [
            'expense' => $expense,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $expense->update($request->validated());

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}
