<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ServerPanel;
use App\Models\User;
use App\Services\Core\AccountService;
use App\Services\Core\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountService $accounts;
    protected WalletService $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accounts = app(AccountService::class);
        $this->wallet = app(WalletService::class);
    }

    protected function makeCategoryWithPanel(): Category
    {
        $panel = ServerPanel::factory()->create(['panel_type' => 'marzban']);
        $category = Category::factory()->create(['server_selection_mode' => 'auto']);
        $category->serverPanels()->attach($panel);

        return $category;
    }

    /** @test */
    public function purchase_fails_with_insufficient_balance_before_touching_the_panel(): void
    {
        Http::fake(); // هیچ درخواستی نباید ارسال شود

        $user = User::factory()->create();
        $category = $this->makeCategoryWithPanel();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 100000]);

        $this->expectException(\App\Exceptions\InsufficientBalanceException::class);

        $this->accounts->purchase($user, $product);

        Http::assertNothingSent();
    }

    /** @test */
    public function successful_purchase_creates_account_and_deducts_wallet(): void
    {
        Http::fake([
            '*/api/admin/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/user' => Http::response(['username' => 'melorin_test', 'subscription_url' => 'https://sub.example.com/x'], 200),
        ]);

        $user = User::factory()->create();
        $category = $this->makeCategoryWithPanel();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 100000]);

        $this->wallet->charge($user, 150000);

        $account = $this->accounts->purchase($user, $product);

        $this->assertEquals('active', $account->status);
        $this->assertEquals(50000, $this->wallet->balance($user));
        $this->assertNotEmpty($account->panel_username);
        $this->assertNotEmpty($account->panel_client_uuid);
        $this->assertDatabaseHas('orders', [
            'id' => $account->order_id,
            'status' => 'account_created',
        ]);
    }

    /** @test */
    public function failed_panel_response_triggers_automatic_refund(): void
    {
        Http::fake([
            '*/api/admin/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/user' => Http::response(['detail' => 'username already exists'], 409),
        ]);

        $user = User::factory()->create();
        $category = $this->makeCategoryWithPanel();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 100000]);

        $this->wallet->charge($user, 150000);

        $this->expectException(\RuntimeException::class);

        try {
            $this->accounts->purchase($user, $product);
        } finally {
            // موجودی باید کامل بازگشت داده شده باشد
            $this->assertEquals(150000, $this->wallet->balance($user));
        }
    }

    /** @test */
    public function auto_selection_picks_the_panel_with_fewer_active_accounts(): void
    {
        Http::fake([
            '*/api/admin/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/user' => Http::response(['username' => 'melorin_test'], 200),
        ]);

        $busyPanel = ServerPanel::factory()->create(['active_accounts_count' => 50]);
        $freePanel = ServerPanel::factory()->create(['active_accounts_count' => 2]);

        $category = Category::factory()->create(['server_selection_mode' => 'auto']);
        $category->serverPanels()->attach([$busyPanel->id, $freePanel->id]);

        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 50000]);
        $user = User::factory()->create();
        $this->wallet->charge($user, 50000);

        $account = $this->accounts->purchase($user, $product);

        $this->assertEquals($freePanel->id, $account->server_panel_id);
    }
}
