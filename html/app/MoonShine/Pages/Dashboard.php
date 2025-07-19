<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Purchase;
use Illuminate\Support\Carbon;
use MoonShine\Apexcharts\Components\LineChartMetric;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;

#[\MoonShine\MenuManager\Attributes\SkipMenu]

class Dashboard extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle()
        ];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Начало';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
	{
        $days = 30;
        return [
            Grid::make([
                Column::make(
                    [
                        ValueMetric::make('Всего закупок в базе')
                            ->value(fn() => Purchase::all()->count()),
                    ],
                    colSpan: 6,
                    adaptiveColSpan: 6
                ),
                Column::make(
                    [
                        ValueMetric::make('За последние ' . $days . '  дней')
                            ->value(fn() => Purchase::all()->where('created_at', '>=', Carbon::now()->subDays($days))->count()),
                    ],
                    colSpan: 6,
                    adaptiveColSpan: 6
                ),
            ]),


            LineBreak::make(),

            LineChartMetric::make('Количество закупок за последние ' . $days . ' дней')
                ->line([
                    '44-ФЗ' => Purchase::query()
                        ->selectRaw('COUNT(*) as count, DATE_FORMAT(created_at, "%d.%m.%Y") as date')
                        ->where('created_at', '>=', Carbon::now()->subDays($days))
                        ->where('type_fz', 'fz44')
                        ->groupBy('date')
                        ->pluck('count', 'date')
                        ->toArray(),
                ])
                ->line([
                    '223-ФЗ' => Purchase::query()
                        ->selectRaw('COUNT(*) as count, DATE_FORMAT(created_at, "%d.%m.%Y") as date')
                        ->where('created_at', '>=', Carbon::now()->subDays($days))
                        ->where('type_fz', 'fz223')
                        ->groupBy('date')
                        ->pluck('count', 'date')
                        ->toArray(),
                ], '#78439E', 'line'),
        ];
	}
}
