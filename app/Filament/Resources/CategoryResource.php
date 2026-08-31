<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Models\ServerPanel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** مدیریت دسته‌بندی‌ها / سبد فروش (بند ۴ سند نیازمندی) */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?string $navigationLabel = 'دسته‌بندی‌ها (سبد فروش)';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('نام دسته‌بندی')->required()->maxLength(255),

            Forms\Components\Select::make('server_selection_mode')
                ->label('روش انتخاب سرور')
                ->options([
                    'auto' => 'خودکار (کمترین اکانت فعال)',
                    'manual' => 'دستی توسط کاربر',
                ])
                ->default('auto')
                ->required()
                ->native(false)
                ->helperText('بند ۶ سند: در حالت خودکار، سیستم کم‌بارترین سرور مجاز را انتخاب می‌کند.'),

            Forms\Components\Radio::make('naming_mode')
                ->label('نام‌گذاری اکانت‌های این سبد فروش')
                ->options([
                    'random' => 'خودکار — الگوی «۴حرف‌اول‌سرور_حجم_شماره‌ترتیبی» (مثلاً germ_30_1)',
                    'custom' => 'دلخواه — هنگام خرید از خریدار یک نام لاتین پرسیده شود',
                ])
                ->default('random')
                ->required()
                ->helperText('در حالت «دلخواه»، اگر نامی که کاربر وارد کرده قبلاً استفاده شده باشد، یک عدد ترتیبی به انتهای آن اضافه می‌شود.'),

            Forms\Components\Select::make('serverPanels')
                ->label('سرورهای مجاز این دسته')
                ->relationship('serverPanels', 'name')
                ->multiple()
                ->preload()
                ->searchable(),

            Forms\Components\Toggle::make('status')
                ->label('فعال')
                ->formatStateUsing(fn ($state) => $state === 'active')
                ->dehydrateStateUsing(fn ($state) => $state ? 'active' : 'inactive')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable(),
                Tables\Columns\TextColumn::make('server_selection_mode')->label('انتخاب سرور')
                    ->formatStateUsing(fn ($state) => $state === 'auto' ? 'خودکار' : 'دستی'),
                Tables\Columns\BadgeColumn::make('naming_mode')->label('نام‌گذاری')
                    ->formatStateUsing(fn (string $state): string => $state === 'custom' ? 'دلخواه' : 'خودکار')
                    ->colors(['warning' => 'custom', 'gray' => 'random']),
                Tables\Columns\TextColumn::make('serverPanels_count')->counts('serverPanels')->label('تعداد سرور'),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('تعداد محصول'),
                Tables\Columns\BadgeColumn::make('status')->label('وضعیت')
                    ->colors(['success' => 'active', 'danger' => 'inactive']),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
