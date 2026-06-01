<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $dashboardService;
    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardService::class);
        $this->transactionService = app(TransactionService::class);
    }

    public function test_dashboard_summary_calculates_correctly_with_transfers_and_excludes_zero_amount_tags(): void
    {
        $userA = User::factory()->create(['name' => 'User A']);
        $userB = User::factory()->create(['name' => 'User B']);

        $accountA = Account::create(['name' => 'Savings A', 'balance' => 1000, 'user_id' => $userA->id]);
        $accountB = Account::create(['name' => 'Savings B', 'balance' => 500, 'user_id' => $userB->id]);

        // 1. Create a normal credit and debit for User A with tag
        $this->transactionService->createTransaction([
            'user_id' => $userA->id,
            'account_id' => $accountA->id,
            'amount' => 200,
            'type' => 'credit',
            'tag' => 'Salary',
            'description' => 'Salary'
        ]);

        $this->transactionService->createTransaction([
            'user_id' => $userA->id,
            'account_id' => $accountA->id,
            'amount' => 50,
            'type' => 'debit',
            'tag' => 'Food & Drinks',
            'description' => 'Coffee'
        ]);

        // 2. Create a cross-user transfer from User A to User B
        $this->transactionService->createTransaction([
            'user_id' => $userA->id,
            'from_account_id' => $accountA->id,
            'to_account_id' => $accountB->id,
            'amount' => 300,
            'type' => 'transfer',
            'tag' => 'Rent',
            'description' => 'Rent Payment'
        ]);

        // Fetch dashboard data for User A
        $dataA = $this->dashboardService->getDashboardData($userA->id);

        // Assert that credits sum includes only User A's salary (200)
        $this->assertEquals(200, $dataA['totalCredits']);
        // Assert that debits sum includes Coffee (50) + transfer debit (300) = 350
        $this->assertEquals(350, $dataA['totalDebits']);
        $this->assertEquals(-150, $dataA['netProfit']);

        // Assert that the expense chart in User A's dashboard ONLY contains segments with amount > 0
        $this->assertNotEmpty($dataA['expenseChart']);
        foreach ($dataA['expenseChart'] as $segment) {
            $this->assertTrue($segment['amount'] > 0);
        }

        // Fetch workspace-wide (consolidated) dashboard data
        $dataGlobal = $this->dashboardService->getDashboardData(null);
        // Consolidated credits: 200 Salary + 300 transfer credit for User B = 500
        $this->assertEquals(500, $dataGlobal['totalCredits']);
        // Consolidated debits: 50 Coffee + 300 transfer debit for User A = 350
        $this->assertEquals(350, $dataGlobal['totalDebits']);
        $this->assertEquals(150, $dataGlobal['netProfit']);
    }

    /**
     * Verify that the dashboard controller enforces Spatie permission policies.
     */
    public function test_dashboard_api_enforces_spatie_role_and_permission_checks(): void
    {
        // Seed roles & permissions first
        $rolePermissionService = app(\App\Services\RolePermissionService::class);
        $rolePermissionService->seedFromEnums();

        $userA = User::factory()->create(['name' => 'Member A', 'is_admin' => false]);
        $userB = User::factory()->create(['name' => 'Member B', 'is_admin' => false]);

        // Create accounts for both
        Account::create(['name' => 'Account A', 'balance' => 1000, 'user_id' => $userA->id]);
        Account::create(['name' => 'Account B', 'balance' => 500, 'user_id' => $userB->id]);

        // 1. Query dashboard as User A, requesting User B's user_id
        // Since User A has no roles or 'view_all_dashboards' permission, it MUST be forced back to User A's accounts
        $response = $this->actingAs($userA)->json('GET', '/api/v1/dashboard', ['user_id' => $userB->id]);
        $response->assertStatus(200);

        $accounts = $response->json('data.accounts');
        $this->assertNotEmpty($accounts);
        foreach ($accounts as $account) {
            $this->assertEquals($userA->id, $account['user_id']);
        }

        // 2. Query workspace-wide dashboard as User A (no user_id passed)
        // Since User A has no permission, it MUST be forced back to User A's accounts
        $response = $this->actingAs($userA)->json('GET', '/api/v1/dashboard');
        $response->assertStatus(200);

        $accounts = $response->json('data.accounts');
        $this->assertNotEmpty($accounts);
        foreach ($accounts as $account) {
            $this->assertEquals($userA->id, $account['user_id']);
        }

        // 3. Grant 'view_all_dashboards' permission to User A
        $userA->givePermissionTo('view_all_dashboards');

        // Now querying User B's dashboard should successfully return User B's accounts!
        $response = $this->actingAs($userA)->json('GET', '/api/v1/dashboard', ['user_id' => $userB->id]);
        $response->assertStatus(200);

        $accounts = $response->json('data.accounts');
        $this->assertNotEmpty($accounts);
        foreach ($accounts as $account) {
            $this->assertEquals($userB->id, $account['user_id']);
        }
    }
}
