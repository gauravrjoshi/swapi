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
}
