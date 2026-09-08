<?php

namespace Tests\Feature\TelegramBot;

use App\Channels\TelegramBot\Handlers\MiscHandler;
use App\Channels\TelegramBot\Support\ConversationState;
use App\Channels\TelegramBot\UpdateRouter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

/**
 * پوشش تست برای «🤖 درخواست ربات نماینده و همکاری»:
 * - قبلاً این دکمه فقط پیام ثابتِ «این بخش به‌زودی فعال می‌شود.» می‌داد
 *   و متن دکمه هم با متنی که واقعاً در UpdateRouter چک می‌شد یکی نبود
 *   (باگ دکمه‌ی بی‌صدا — دقیقاً همان الگویی که در ۲.۴.۸ با دکمه‌ی
 *   دعوت از دوستان رخ داده بود).
 * - ادمین ربات باید پیام ثابتِ نیاز به نسخه‌ی پرو را ببیند و وارد
 *   جریان توضیحات نشود.
 * - کاربر عادی باید منتظر توضیحات بماند و توضیحاتش برای تمام
 *   admin_ids تنظیم‌شده ارسال شود.
 */
class ResellerRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function bot_admin_sees_the_pro_version_message_and_is_not_put_into_the_description_flow(): void
    {
        config(['telegram.admin_ids' => ['999']]);

        $admin = User::factory()->create(['telegram_id' => 999]);

        $telegram = Mockery::mock(Api::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === 999
                && str_contains($params['text'], 'نسخه‌ی پرو')
                && str_contains($params['text'], 'https://t.me/melorinpro'))
            ->andReturn(new Message([
                'message_id' => 1,
                'date' => time(),
                'chat' => ['id' => 999, 'type' => 'private'],
                'text' => 'test',
            ]));

        $this->app->instance(Api::class, $telegram);

        app(MiscHandler::class)->resellerRequestStart(999, $admin);

        $state = app(ConversationState::class)->find(999);
        $this->assertNotSame(ConversationState::RESELLER_REQUEST_AWAITING_DESCRIPTION, $state->step);
    }

    /** @test */
    public function regular_user_is_prompted_and_put_into_the_awaiting_description_state(): void
    {
        config(['telegram.admin_ids' => []]);

        $user = User::factory()->create(['telegram_id' => 123]);

        $telegram = Mockery::mock(Api::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === 123
                && str_contains($params['text'], 'توضیحات خود را برای ثبت درخواست نمایندگی'))
            ->andReturn(new Message([
                'message_id' => 1,
                'date' => time(),
                'chat' => ['id' => 123, 'type' => 'private'],
                'text' => 'test',
            ]));

        $this->app->instance(Api::class, $telegram);

        app(MiscHandler::class)->resellerRequestStart(123, $user);

        $state = app(ConversationState::class)->find(123);
        $this->assertSame(ConversationState::RESELLER_REQUEST_AWAITING_DESCRIPTION, $state->step);
    }

    /** @test */
    public function submitted_description_is_broadcast_to_every_configured_admin_id_and_state_is_reset(): void
    {
        config(['telegram.admin_ids' => ['555', '666']]);

        $user = User::factory()->create([
            'telegram_id' => 123,
            'full_name' => 'کاربر تستی',
        ]);

        app(ConversationState::class)->set(
            123,
            ConversationState::RESELLER_REQUEST_AWAITING_DESCRIPTION,
            [],
            $user
        );

        $telegram = Mockery::mock(Api::class);

        $telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === '555'
                && str_contains($params['text'], 'کاربر تستی')
                && str_contains($params['text'], '۵۰۰ نفر مشتری دارم'))
            ->andReturn(new Message([
                'message_id' => 1,
                'date' => time(),
                'chat' => ['id' => 555, 'type' => 'private'],
                'text' => 'test',
            ]));

        $telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === '666'
                && str_contains($params['text'], 'کاربر تستی'))
            ->andReturn(new Message([
                'message_id' => 2,
                'date' => time(),
                'chat' => ['id' => 666, 'type' => 'private'],
                'text' => 'test',
            ]));

        $telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === 123
                && str_contains($params['text'], 'ثبت و برای بررسی ارسال شد'))
            ->andReturn(new Message([
                'message_id' => 3,
                'date' => time(),
                'chat' => ['id' => 123, 'type' => 'private'],
                'text' => 'test',
            ]));

        $this->app->instance(Api::class, $telegram);

        app(MiscHandler::class)->resellerRequestSubmit(
            123,
            $user,
            'من قبلاً ۵۰۰ نفر مشتری دارم و می‌خواهم نماینده شوم.'
        );

        $state = app(ConversationState::class)->find(123);
        $this->assertSame(ConversationState::IDLE, $state->step);
    }

    /** @test */
    public function missing_admin_ids_config_does_not_break_the_flow_and_is_logged(): void
    {
        config(['telegram.admin_ids' => []]);
        Log::spy();

        $user = User::factory()->create(['telegram_id' => 123]);

        $telegram = Mockery::mock(Api::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === 123)
            ->andReturn(new Message([
                'message_id' => 1,
                'date' => time(),
                'chat' => ['id' => 123, 'type' => 'private'],
                'text' => 'test',
            ]));

        $this->app->instance(Api::class, $telegram);

        app(MiscHandler::class)->resellerRequestSubmit(
            123,
            $user,
            'توضیحات درخواست نمایندگی.'
        );

        Log::shouldHaveReceived('warning')->once();
    }

    /** @test */
    public function tapping_the_menu_button_actually_routes_to_the_handler(): void
    {
        // این دقیقاً همان رگرسیونی است که باعث شد این دکمه در نسخه‌های
        // قبلی بی‌صدا از کار بیفتد: متن دکمه در Keyboards عوض شده بود
        // ولی UpdateRouter هنوز متن قدیمی را چک می‌کرد.

        config(['telegram.admin_ids' => []]);

        $user = User::factory()->create(['telegram_id' => 123]);

        $telegram = Mockery::mock(Api::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (array $params) => str_contains(
                $params['text'],
                'توضیحات خود را برای ثبت درخواست نمایندگی'
            ))
            ->andReturn(new Message([
                'message_id' => 1,
                'date' => time(),
                'chat' => ['id' => 123, 'type' => 'private'],
                'text' => 'test',
            ]));

        $this->app->instance(Api::class, $telegram);

        $update = new Update([
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'chat' => ['id' => 123, 'type' => 'private'],
                'text' => '🤖 درخواست ربات نماینده و همکاری',
            ],
        ]);

        app(UpdateRouter::class)->handle($update, $user, 123);

        $state = app(ConversationState::class)->find(123);
        $this->assertSame(
            ConversationState::RESELLER_REQUEST_AWAITING_DESCRIPTION,
            $state->step
        );
    }
}
