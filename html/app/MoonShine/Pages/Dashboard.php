<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\File;
use App\Models\Purchase;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use MoonShine\Apexcharts\Components\LineChartMetric;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Components\Title;

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
        $days_array = [];
        for($i = $days;$i >= 0;$i--){
            $days_array[Carbon::now()->subDays($i)->toDateString()] = 0;
        }

        return [
            Box::make([
                Title::make('Закупки'),
                Grid::make([
                    Column::make(
                        [
                            ValueMetric::make('Сегодня')
                                ->value(fn() => Purchase::query()->whereDate('created_at', '=', Carbon::now()->toDateString())->count()),
                        ],
                        colSpan: 3,
                        adaptiveColSpan: 3
                    ),
                    Column::make(
                        [
                            ValueMetric::make('Вчера')
                                ->value(fn() => Purchase::query()->whereDate('created_at', '=', Carbon::now()->subDays()->toDateString())->count()),
                        ],
                        colSpan: 3,
                        adaptiveColSpan: 3
                    ),
                    Column::make(
                        [
                            ValueMetric::make('За последние ' . $days . '  дней')
                                ->value(fn() => Purchase::query()->where('created_at', '>=', Carbon::now()->subDays($days)->toDateString())->count()),
                        ],
                        colSpan: 3,
                        adaptiveColSpan: 3
                    ),
                    Column::make(
                        [
                            ValueMetric::make('Всего закупок в базе')
                                ->value(fn() => Purchase::query()->count()),
                        ],
                        colSpan: 3,
                        adaptiveColSpan: 3
                    ),
                ]),


                LineBreak::make(),

                LineChartMetric::make('Закупки за последние ' . $days . ' дней')
                    ->line([
                        '44-ФЗ' => array_merge($days_array, Purchase::query()
                            ->selectRaw('COUNT(*) as count, DATE_FORMAT(created_at, "%Y-%m-%d") as date')
                            ->where('created_at', '>=', Carbon::now()->subDays($days)->toDateString())
                            ->where('type_fz', 'fz44')
                            ->groupBy(['date'])
                            ->orderBy('date')
                            ->pluck('count', 'date')
                            ->toArray()),
                    ])
                    ->line([
                        '223-ФЗ' => array_merge($days_array, Purchase::query()
                            ->selectRaw('COUNT(*) as count, DATE_FORMAT(created_at, "%Y-%m-%d") as date')
                            ->where('created_at', '>=', Carbon::now()->subDays($days)->toDateString())
                            ->where('type_fz', 'fz223')
                            ->groupBy(['date'])
                            ->orderBy('date')
                            ->pluck('count', 'date')
                            ->toArray()),
                    ], '#78439E', 'line'),

            ]),

            LineBreak::make(),

            Box::make([
                Title::make('Файлы'),
                Grid::make([
                    Column::make(
                        [
                            ValueMetric::make('Сегодня')
                                ->value(fn() => File::query()->whereDate('created_at', '=', Carbon::now()->toDateString())->count()),
                        ],
                        colSpan: 3,
                        adaptiveColSpan: 3
                    ),
                    Column::make(
                        [
                            ValueMetric::make('Вчера')
                                ->value(fn() => File::query()->whereDate('created_at', '=', Carbon::now()->subDays()->toDateString())->count()),
                        ],
                        colSpan: 3,
                        adaptiveColSpan: 3
                    ),
                    Column::make(
                        [
                            ValueMetric::make('За последние ' . $days . '  дней')
                                ->value(fn() => File::query()->where('created_at', '>=', Carbon::now()->subDays($days)->toDateString())->count()),
                        ],
                        colSpan: 3,
                        adaptiveColSpan: 3
                    ),
                    Column::make(
                        [
                            ValueMetric::make('Всего файлов в базе')
                                ->value(fn() => File::query()->count()),
                        ],
                        colSpan: 3,
                        adaptiveColSpan: 3
                    ),
                ]),


                LineBreak::make(),

                LineChartMetric::make('Файлов за последние ' . $days . ' дней')
                    ->line([
                        'Файлы' => File::query()
                            ->selectRaw('COUNT(*) as count, DATE_FORMAT(created_at, "%Y-%m-%d") as date')
                            ->where('created_at', '>=', Carbon::now()->subDays($days)->toDateString())
                            ->groupBy(['date'])
                            ->orderBy('date')
                            ->pluck('count', 'date')
                            ->toArray(),
                    ], '#78839E', 'line'),

            ]),

            LineBreak::make(),

                Box::make([
                    Title::make('Обновления ПО'),
                    Grid::make([
                        Column::make(
                            [
                                ValueMetric::make('[' .
                                    Carbon::createFromTimestampUTC($this->containersInfo()['git']['purchases']['date'])
                                        ->format('d-m-Y') . '] ' . $this->containersInfo()['git']['purchases']['message']
                                )->value(fn() => 'Сервис закупок'),
                            ],
                            colSpan: 6,
                            adaptiveColSpan: 6
                        ),
                        Column::make(
                            [
                                ValueMetric::make('[' .
                                    Carbon::createFromTimestampUTC($this->containersInfo()['git']['search']['date'])
                                        ->format('d-m-Y') . '] ' . $this->containersInfo()['git']['search']['message']
                                )->value(fn() => 'Поисковая машина'),
                            ],
                            colSpan: 6,
                            adaptiveColSpan: 6
                        ),
                    ]),
                ]),
        ];
    }

    private function containersInfo(): array
    {
        try {
            return Cache::remember('containers_informer', now()->addHour(),
                function () {
                    $return = Http::get(env('CONTAINERS_INFO_HOST', 'http://localhost'))->json();
                    return is_array($return) ? $return : [];
                }
            );
        } catch (Exception $exception) {
            return ['error' => $exception->getMessage()];
        }
    }
}
