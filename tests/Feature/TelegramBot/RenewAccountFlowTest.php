<?php

namespace Tests\Feature\TelegramBot;

use App\Channels\TelegramBot\Handlers\AccountsHandler;
use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\ServerPanel;
use App\Models\User;
use App\Services\Core\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RenewAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAccount(User $user, float $price = 100000): Account
    {
        $panel = ServerPanel::factory()->create(['panel_type' => 'marzban']);
        $category = Category::factory()->create();
        $category->serverPanels()->attach($panel);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => $price]);

        return Account::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'server_panel_id' => $panel->id,
            'panel_username' => 'melorin_existing',
            'status' => 'active',
            'expires_at' => now()->addDays(5),
        ]);
    }

    /** @test */
    public function successful_renewal_deducts_wallet_and_extends_expiry(): void
    {
        Http::fake([
            '*/api/admin/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/user/*' => Http::response(['username' => 'melorin_existing'], 200),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        $user = User::factory()->create(['telegram_id' => 555]);
        $account = $this->makeAccount($user, 100000);
        app(WalletService::class)->charge($user, 150000);

        app(AccountsHandler::class)->renew(555, $user, $account->id);

        $this->assertEquals(50000, app(WalletService::class)->balance($user->fresh()));
        $this->assertTrue($account->fresh()->expires_at->isAfter(now()->addDays(30)));
    }

    /**
     * این تست دقیقاً باگی را پوشش می‌دهد که پیدا و رفع شد: اگر تمدید
     * روی پنل شکست بخورد، کیف پول باید کامل به حالت قبل برگردد — نه
     * اینکه پول کسر شده بماند بدون دریافت خدمت.
     * @test
     */
    public function failed_panel_renewal_refunds_the_wallet_deduction(): void
    {
        Http::fake([
            '*/api/admin/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/user/*' => Http::response(['detail' => 'panel unreachable'], 500),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        $user = User::factory()->create(['telegram_id' => 556]);
        $account = $this->makeAccount($user, 100000);
        app(WalletService::class)->charge($user, 150000);

        app(AccountsHandler::class)->renew(556, $user, $account->id);

        // موجودی باید دقیقاً به ۱۵۰,۰۰۰ برگردد — نه ۵۰,۰۰۰ باقی‌مانده
        $this->assertEquals(150000, app(WalletService::class)->balance($user->fresh()));

        $this->assertDatabaseHas('wallet_transactions', [
            'type' => 'refund',
        ]);
    }

    /** @test */
    public function insufficient_balance_never_touches_the_panel(): void
    {
        Http::fake();

        $user = User::factory()->create(['telegram_id' => 557]);
        $account = $this->makeAccount($user, 100000);
        app(WalletService::class)->charge($user, 50000); // کمتر از قیمت تمدید

        app(AccountsHandler::class)->renew(557, $user, $account->id);

        Http::assertNothingSent();
        $this->assertEquals(50000, app(WalletService::class)->balance($user->fresh()));
    }
}
