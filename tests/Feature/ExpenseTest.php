<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\Expense;
use App\Models\HotelBranch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create(['is_default' => true]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewExpenses->value,
            PermissionName::ManageExpenses->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'description' => 'Generator diesel',
            'amount' => 45000,
            'category' => 'Utilities',
            'expense_date' => '2026-10-10',
        ], $overrides);
    }

    public function test_index_lists_created_expenses_with_creator(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('expenses.store'), $this->payload())
            ->assertRedirect(route('expenses.index'));

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Expenses/Index')
                    ->has('expenses.data', 1)
                    ->where('expenses.data.0.creator.name', $this->user->name),
            );
    }

    public function test_store_creates_expense(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('expenses.store'), $this->payload())
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'branch_id' => $this->branch->id,
            'description' => 'Generator diesel',
            'amount' => 45000,
            'category' => 'Utilities',
            'status' => 'completed',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('expenses.store'), ['description' => 'Incomplete'])
            ->assertSessionHasErrors(['amount', 'category', 'expense_date']);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('expenses.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('expenses.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('expenses', 0);
    }

    private function makeExpense(array $overrides = []): Expense
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('expenses.store'), $this->payload());

        return Expense::firstOrFail();
    }

    public function test_edit_page_loads_for_existing_expense(): void
    {
        $expense = $this->makeExpense();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Expenses/Edit')
                    ->where('expense.id', $expense->id),
            );
    }

    public function test_update_persists_changes(): void
    {
        $expense = $this->makeExpense();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->put(route('expenses.update', $expense), $this->payload([
                'amount' => 99000,
                'category' => 'Maintenance',
            ]))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'amount' => 99000,
            'category' => 'Maintenance',
        ]);
    }

    public function test_complete_marks_pending_expense_completed(): void
    {
        $expense = Expense::create([
            'expense_number' => 'EXP-TEST-PENDING',
            'branch_id' => $this->branch->id,
            'description' => 'Pending expense',
            'amount' => 100,
            'category' => 'Maintenance',
            'expense_date' => '2026-10-10',
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('expenses.complete', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'completed',
        ]);
    }

    public function test_destroy_removes_expense(): void
    {
        $expense = $this->makeExpense();

        $this->actingAs($this->user)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertSame(0, Expense::count());
    }
}
