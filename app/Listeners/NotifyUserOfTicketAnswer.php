<?php

namespace App\Listeners;

use App\Events\TicketAnswered;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

/**
 * پاسخ ادمین در پنل Filament باید در همان ربات تلگرام به دست کاربر
 * برسد — کاربر هیچ راه دیگری برای دیدن پنل ندارد. دقیقاً هم‌الگو با
 * NotifyUserOfPaymentConfirmation.
 */
class NotifyUserOfTicketAnswer
{
    public function __construct(protected Api $telegram) {}

    public function handle(TicketAnswered $event): void
    {
        $message = $event->message->fresh(['ticket.user']);
        $user = $message->ticket->user;

        if (! $user?->telegram_id) {
            return;
        }

        $text = "💬 پاسخ پشتیبانی به تیکت #{$message->ticket_id} ({$message->ticket->subject}):\n\n"
            .$message->message
            ."\n\nبرای پاسخ، به 🎧 «پشتیبانی» بروید و پیام خود را ارسال کنید.";

        try {
            $this->telegram->sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ارسال پاسخ تیکت به کاربر ناموفق بود.', [
                'ticket_id' => $message->ticket_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
