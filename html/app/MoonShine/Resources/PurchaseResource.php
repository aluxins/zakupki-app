<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\File;
use Barryvdh\Debugbar\Facades\Debugbar;
use App\Models\Purchase;
use App\MoonShine\Pages\Purchase\PurchaseIndexPage;
use App\MoonShine\Pages\Purchase\PurchaseFormPage;
use App\MoonShine\Pages\Purchase\PurchaseDetailPage;
use Exception;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Fieldset;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Range;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;
use RomanStruk\ManticoreScoutEngine\Mysql\Builder;

/**
 * @extends ModelResource<Purchase, PurchaseIndexPage, PurchaseFormPage, PurchaseDetailPage>
 */
class PurchaseResource extends ModelResource
{
    protected string $model = Purchase::class;

    protected string $title = 'Закупки';

    //protected bool $stickyTable = true;

    protected bool $createInModal = true;

    protected bool $editInModal = true;

    protected bool $detailInModal = true;

    /**
     * @return list<Page>
     */
    protected function pages(): array
    {
        return [
            PurchaseIndexPage::class,
            //PurchaseFormPage::class,
            PurchaseDetailPage::class,
        ];
    }

    /**
     * @param Purchase $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    protected function rules(mixed $item): array
    {
        return [];
    }

    protected function indexFields(): iterable
    {
        return [
            //ID::make()->sortable(),

            Fieldset::make('', [

                Fieldset::make('Закупка', [
                    Text::make('purchase_number')->changePreview(
                        fn($value, Text $ctx) => 'ID:' . $ctx->getData()->getOriginal()->id . ' Номер: ' . $value
                    ),

                    Date::make('Дата публикации', 'published_at')
                        ->format("d.m.Y")
                        ->sortable(),

                    Url::make('purchase_number')
                        ->changePreview(
                            fn($value, Url $ctx) =>
                                '( <a target="_blank" href="https://zakupki.gov.ru/epz/order/notice/ea20/view/common-info.html?regNumber=' . $value . '">zakupki.gov.ru &nearhk;</a> )'
                        ),

                    Text::make(__('object_info'), 'object_info'),

                    HasMany::make('files')->fields([
                        //Text::make('file_name'),
                        Url::make(__('Файлы'), 'url')
                            ->changePreview(
                                fn($value, Url $ctx) => '<a href="' . $value . '">' . $ctx->getData()->getOriginal(
                                    )->file_name . '</a>'
                            ),
                    ]),
                ]),

                Json::make('Text')
                    ->changeFill(
                        function (Purchase $purchase) {
                            try {
                                $validatedData = request()->validate([
                                    'files_search' => ['min:1', 'string', 'nullable']
                                ]);
                                if (empty($validatedData['files_search'])) {
                                    throw new Exception();
                                }
                                $documents = File::search(
                                    $validatedData['files_search'],
                                    fn(Builder $builder) => $builder->highlight(
                                        [
                                            'around' => 10,
                                            'limit' => 1024,
                                            'before_match' => '<i class="text-blue">',
                                            'after_match' => '</i>'
                                        ]
                                    )->select(['id', 'name', 'url'])
                                )->where('reg_number', $purchase->purchase_number);
                                Debugbar::info($documents->raw());
                                return json_decode(
                                    json_encode(
                                        $documents->raw()['hits'],
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                    )
                                );
                            } catch (Exception $e) {
                                return [];
                            }
                        }
                    )->fields([
                        Fieldset::make('Поиск в файлах', [

                            Url::make('url')
                                ->changePreview(
                                    fn(
                                        $value,
                                        Url $ctx
                                    ) => '<a class="font-bold" href="' . $value . '">' . $ctx->getData()->getOriginal(
                                        )->name . '</a>'
                                ),

                            //LineBreak::make(),

                            Text::make(
                                'highlight',
                                'highlight',
                            )->changePreview(fn($value, Text $ctx) => '<div style="text-wrap: initial;">&nbsp;&rdca; '
                                . str_replace(
                                    '...',
                                    "&hellip; <br>&nbsp;&rdca; ",
                                    mb_substr($value, 5, (int)mb_strrpos($value, '|') - 10)
                                )
                                . '&hellip;</div>')

                        ]),
                    ]),

            ])->customWrapperAttributes(['width' => '99%']),

            Number::make(__('Цена'), 'max_price', fn($item) => number_format($item->max_price, 2) . ' ₽')
                ->sortable()->customWrapperAttributes(['style' => 'vertical-align:top']),

            Date::make('Создано', 'published_at')->format('d.m.Y')
                ->sortable()->customWrapperAttributes(['style' => 'vertical-align:top']),

            Text::make(__('Регион'), 'region')
                ->sortable()->customWrapperAttributes(['style' => 'vertical-align:top']),

            //HasMany::make('files', 'files', FileResource::class)->disableOutside(),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Range::make('Начальная цена', 'max_price')->max(100000000),
            Text::make('Регион', 'region'),
            DateRange::make('Дата публикации', 'published_at'),
            Text::make('Поиск по файлам', 'object_info')->setAttribute('name', 'files_search'),
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->empty();
            //->only(Action::VIEW);
    }
}
