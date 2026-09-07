<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

/**
 * روند فروش ۱۴ روز اخیر (بند ۱۷ سند: «فروش روزانه/هفتگی»). از
 * ChartWidget مستقیم استفاده شده (نه LineChartWidget که در نسخه‌ی نصب‌شده
 * deprecated است و فقط getType() را برمی‌گرداند).
 */
class SalesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'روند فروش ۱۴ روز اخیر';

    protected static ?string $pollingInterval = '60s';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $i) => today()->subDays($i));

        $revenueByDay = Order::query()
            ->whereIn('status', ['paid', 'account_created'])
            ->where('created_at', '>=', today()->subDays(13))
            ->selectRaw('DATE(created_at) as day, SUM(sold_price) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        return [
            'datasets' => [
                [
                    'label' => 'فروش (تومان)',
                    'data' => $days->map(fn ($day) => (float) ($revenueByDay[$day->toDateString()] ?? 0))->values(),
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('m-d'))->values(),
        ];
    }
}
