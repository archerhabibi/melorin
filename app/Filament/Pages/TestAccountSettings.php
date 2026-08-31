<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\TestAccountSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * تنظیمات اکانت تست (رفع بند مربوطه در گزارش Audit فاز A). یک صفحه‌ی
 * تک‌فرمی singleton — دقیقاً هم‌الگو با این‌که AffiliateSetting هم
 * تک‌رکوردی است، فقط اینجا برخلاف AffiliateSetting یک UI هم دارد چون
 * بدون آن ادمین راهی برای فعال/تنظیم کردنش ندارد.
 */
class TestAccountSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?string $navigationLabel = 'اکانت تست';

    protected static string $view = 'filament.pages.test-account-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            TestAccountSetting::current()->only(['enabled', 'product_id', 'max_per_user'])
        );
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('enabled')
                    ->label('اکانت تست فعال باشد')
                    ->helperText('طبق سیاست انتشار، تا وقتی این گزینه فعال نباشد یا محصولی انتخاب نشده باشد، دکمه‌ی «دریافت اکانت تست» اصلاً در منوی ربات نشان داده نمی‌شود.'),

                Forms\Components\Select::make('product_id')
                    ->label('محصول اکانت تست')
                    ->helperText('یک محصول با قیمت ۰ تومان بسازید (در بخش «محصولات و تعرفه‌ها») — می‌توانید آن را در یک دسته‌بندی جدا و غیرفعال/مخفی از منوی خرید عادی قرار دهید — و همان را اینجا انتخاب کنید. حجم و مدت اعتبار اکانت تست از همین محصول خوانده می‌شود.')
                    ->options(fn () => Product::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(fn (Forms\Get $get) => (bool) $get('enabled')),

                Forms\Components\TextInput::make('max_per_user')
                    ->label('حداکثر تعداد اکانت تست به‌ازای هر کاربر')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        TestAccountSetting::current()->update($data);

        Notification::make()
            ->title('تنظیمات اکانت تست ذخیره شد')
            ->success()
            ->send();
    }
}
