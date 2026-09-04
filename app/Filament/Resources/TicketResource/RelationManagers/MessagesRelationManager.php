<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use App\Events\TicketAnswered;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * این بخش اصلیِ رفع «جعبه‌ی سیاه بودنِ» تیکت‌هاست: نمایش گفتگو به ترتیب
 * زمانی و دکمه‌ی «پاسخ» که هم پیام را در ticket_messages ثبت می‌کند، هم
 * وضعیت تیکت را answered می‌کند، هم TicketAnswered را منتشر می‌کند تا
 * کاربر در تلگرام پاسخ را ببیند (ر.ک. NotifyUserOfTicketAnswer).
 */
class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'گفتگو';

    protected static ?string $recordTitleAttribute = 'message';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('message')
                ->label('متن پاسخ')
                ->required()
                ->rows(5),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('sender_type')
                    ->label('فرستنده')
                    ->badge()
                    ->color(fn (string $state) => $state === 'admin' ? 'success' : 'gray')
                    ->formatStateUsing(fn ($record) => $record->senderLabel()),

                Tables\Columns\TextColumn::make('message')
                    ->label('پیام')
                    ->wrap()
                    ->limit(500),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->headerActions([
                Tables\Actions\Action::make('reply')
                    ->label('✍️ پاسخ به تیکت')
                    ->color('success')
                    ->visible(fn () => $this->getOwnerRecord()->isOpen())
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('متن پاسخ')
                            ->required()
                            ->rows(5),
                    ])
                    ->action(function (array $data) {
                        /** @var Ticket $ticket */
                        $ticket = $this->getOwnerRecord();

                        $message = $ticket->messages()->create([
                            'sender_type' => 'admin',
                            'sender_id' => Auth::guard('admin')->id(),
                            'message' => $data['message'],
                        ]);

                        $ticket->update(['status' => 'answered']);

                        TicketAnswered::dispatch($message);

                        Notification::make()->title('پاسخ ارسال شد.')->success()->send();
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function canCreate(): bool
    {
        // ساخت پیام فقط از طریق دکمه‌ی «پاسخ» (headerActions) انجام می‌شود
        // که علاوه‌بر پیام، وضعیت تیکت را هم آپدیت و رویداد را هم منتشر
        // می‌کند؛ اکشن پیش‌فرض create این کارها را انجام نمی‌دهد.
        return false;
    }

    public function canEdit($record): bool
    {
        return false;
    }

    public function canDelete($record): bool
    {
        return false;
    }
}
