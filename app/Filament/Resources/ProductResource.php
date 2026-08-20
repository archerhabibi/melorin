<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** مدیریت محصولات و تعرفه‌ها (بند ۸ سند نیازمندی) */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?string $navigationLabel = 'محصولات و تعرفه‌ها';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('دسته‌بندی')
                ->relationship('category', 'name')
                ->required()
                ->searchable()
                ->preload(),

            Forms\Components\TextInput::make('name')->label('نام محصول')->required()->maxLength(255),

            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('price')
                    ->label('قیمت پایه (تومان)')
                    ->numeric()
                    ->required()
                    ->suffix('تومان'),

                Forms\Components\TextInput::make('traffic_gb')
                    ->label('حجم (گیگابایت)')
                    ->numeric()
                    ->helperText('خالی = نامحدود'),

                Forms\Components\TextInput::make('duration_days')
                    ->label('مدت اعتبار (روز)')
                    ->numeric()
                    ->required(),
            ]),

            Forms\Components\Select::make('protocol_id')
                ->label('پروتکل')
                ->relationship('protocol', 'name')
                ->searchable()
                ->preload(),

            Forms\Components\Radio::make('naming_mode')
                ->label('نام‌گذاری اکانت‌های این سبد فروش')
                ->options([
                    'random' => 'خودکار — الگوی «حروف‌اول‌سرور_حجم_شماره‌ترتیبی» (مثلاً ger_30_1)',
                    'custom' => 'دلخواه — هنگام خرید از خریدار یک نام لاتین پرسیده شود',
                ])
                ->default('random')
                ->required()
                ->helperText('در حالت «دلخواه»، اگر نامی که کاربر وارد کرده قبلاً استفاده شده باشد، یک عدد ترتیبی به انتهای آن اضافه می‌شود.'),

            Forms\Components\TextInput::make('sale_limit')
                ->label('محدودیت فروش (تعداد)')
                ->numeric()
                ->helperText('خالی = بدون محدودیت'),

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
                Tables\Columns\TextColumn::make('category.name')->label('دسته‌بندی'),
                Tables\Columns\TextColumn::make('price')->label('قیمت')->money('IRT', divideBy: 1)->sortable(),
                Tables\Columns\TextColumn::make('duration_days')->label('مدت (روز)'),
                Tables\Columns\TextColumn::make('traffic_gb')->label('حجم (گیگ)')->placeholder('نامحدود'),
                Tables\Columns\BadgeColumn::make('naming_mode')->label('نام‌گذاری')
                    ->formatStateUsing(fn (string $state): string => $state === 'custom' ? 'دلخواه' : 'خودکار')
                    ->colors(['warning' => 'custom', 'gray' => 'random']),
                Tables\Columns\BadgeColumn::make('status')->label('وضعیت')
                    ->colors(['success' => 'active', 'danger' => 'inactive']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('دسته‌بندی')
                    ->relationship('category', 'name'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
