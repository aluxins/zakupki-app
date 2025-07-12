<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Helpers\CodesSubjects;
use App\Models\File;
use App\Models\Purchase;
use App\MoonShine\Pages\Purchase\PurchaseDetailPage;
use App\MoonShine\Pages\Purchase\PurchaseIndexPage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Fieldset;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Range;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;
use RomanStruk\ManticoreScoutEngine\Mysql\Builder as ManticoreBuilder;
use Throwable;

/**
 * Ресурс для управления закупками в админ-панели
 *
 * @extends ModelResource<Purchase>
 */
class PurchaseResource extends ModelResource
{
    protected string $model = Purchase::class;

    protected string $title = 'Закупки';

    //protected bool $stickyTable = true;

    protected bool $createInModal = true;

    protected bool $editInModal = true;

    protected bool $detailInModal = true;

    protected string $sortColumn = 'published_at';
    protected SortDirection $sortDirection = SortDirection::DESC;

    /**
     * Возвращает список страниц ресурса
     *
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
     * Правила валидации для ресурса
     *
     * @param Purchase $item
     * @return array<string, string[]|string>
     */
    protected function rules(mixed $item): array
    {
        return [];
    }

    /**
     * Поля для отображения в списке
     */
    protected function indexFields(): iterable
    {
        return [
            //ID::make()->sortable(),

            $this->createMainFieldset(),

            Number::make(__('Цена'), 'max_price', fn($item) => number_format($item->max_price, 2) . ' ₽')
                ->sortable()->customWrapperAttributes(['style' => 'vertical-align:top']),

            Date::make('Создано', 'published_at')->format('d.m.Y')
                ->sortable()->customWrapperAttributes(['style' => 'vertical-align:top']),

            Text::make(__('Регион'), 'region', fn($item) => (new CodesSubjects)((int) $item->region))
                ->sortable()->customWrapperAttributes(['style' => 'vertical-align:top']),

            //HasMany::make('files', 'files', FileResource::class)->disableOutside(),
        ];
    }

    /**
     * Создает основной набор полей закупки.
     */
    private function createMainFieldset(): Fieldset
    {
        return Fieldset::make('', [
            $this->createPurchaseFields(),
            $this->createFileSearchField(),
        ])->customWrapperAttributes(['width' => '99%']);
    }

    /**
     * Создает поля с информацией о закупке.
     */
    private function createPurchaseFields(): Fieldset
    {
        return Fieldset::make('Закупка', [
            Text::make('purchase_number')
                ->changePreview(fn($value, Text $ctx) =>
                    'ID:' . $ctx->getData()->getOriginal()->id . ' Номер: ' . $value
                ),

            Date::make('Дата публикации', 'published_at')
                ->format('d.m.Y')
                ->sortable(),

            $this->createPurchaseUrlField(),
            Text::make(__('object_info'), 'object_info'),
            $this->createFilesField(),
        ]);
    }

    /**
     * Создает гиперссылку для перехода на страницу закупки на сайте https://zakupki.gov.ru
     */
    private function createPurchaseUrlField(): Url
    {
        return Url::make('purchase_number')
            ->changePreview(fn($value) =>
                sprintf(
                    '( <a target="_blank" href="https://zakupki.gov.ru/epz/order/notice/ea20/view/common-info.html?regNumber=%s">zakupki.gov.ru &nearhk;</a> )',
                    $value
                )
            );
    }

    /**
     * Создает список файлов.
     */
    private function createFilesField(): HasMany
    {
        return HasMany::make('files')->fields([
            Url::make(__('Файлы'), 'url')
                ->changePreview(fn($value, Url $ctx) =>
                    sprintf(
                        '<a href="%s">%s</a>',
                        $value,
                        $ctx->getData()->getOriginal()->file_name
                    )
                ),
        ]);
    }

    /**
     * Создает блок результата поиска по файлам.
     */
    private function createFileSearchField(): Json
    {
        return Json::make('Text')
            ->changeFill(function (Purchase $purchase) {
                try {
                    return $this->searchInFiles($purchase);
                } catch (Throwable $e) {
                    Log::error('Error searching in files: ' . $e->getMessage(), [
                        'purchase_number' => $purchase->purchase_number,
                        'exception' => $e
                    ]);
                    return [];
                }
            })
            ->fields([
                $this->createFileSearchResultsFieldset()
            ]);
    }

    /**
     * Выполняет поиск в файлах
     */
    private function searchInFiles(Purchase $purchase): array
    {
        $validatedData = request()->validate([
            'filter.files_search' => ['min:1', 'string', 'nullable'],
            'filter.strict_search' => ['boolean', 'nullable']
        ]);

        if (empty($validatedData['filter']['files_search'])) {
            return [];
        }

        $documents = File::search(
            $validatedData['filter']['files_search'],
            function (ManticoreBuilder $builder) use ($validatedData) {
                $highlight_options = Config::array('manticore.highlight_options', []);
                if (!empty($validatedData['filter']['strict_search'])) { // Включение строгого режима поиска.
                    $builder
                        ->setProximitySearchOperator(count(preg_split('/\s+/', $validatedData['filter']['files_search'])));
                    if (isset($highlight_options['around'])) {
                        $highlight_options['around'] *= 2;
                    }
                }
                $builder->highlight($highlight_options)
                    ->select(['id', 'name', 'url']);
                return $builder;
            }
        )->where('reg_number', $purchase->purchase_number);

        return json_decode(
            json_encode(
                $documents->raw()['hits'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }

    /**
     * Создает набор полей для результатов поиска
     */
    private function createFileSearchResultsFieldset(): Fieldset
    {
        return Fieldset::make('Поиск в файлах', [
            $this->createFileUrlField(),
            $this->createHighlightField(),
        ]);
    }

    /**
     * Создает поле для URL файла
     */
    private function createFileUrlField(): Url
    {
        return Url::make('url')
            ->changePreview(fn($value, Url $ctx) =>
                sprintf(
                    '<a class="font-bold" href="%s">%s</a>',
                    $value,
                    $ctx->getData()->getOriginal()->name
                )
            );
    }

    /**
     * Создает поле для подсветки результатов поиска
     */
    private function createHighlightField(): Text
    {
        return Text::make('highlight', 'highlight')
            ->changePreview(fn($value) =>
                sprintf(
                    '<div style="text-wrap: initial;">&nbsp;&rdca; %s&hellip;</div>',
                    str_replace(
                        '...',
                        "&hellip; <br>&nbsp;&rdca; ",
                        mb_substr($value, 5, (int)mb_strrpos($value, '|') - 10)
                    )
                )
            );
    }

    protected function filters(): iterable
    {
        return [
            Range::make('Начальная цена', 'max_price')->max(100000000),
            Text::make('Регион', 'region'),
            DateRange::make('Дата публикации', 'published_at'),
            Text::make('Поиск по файлам', 'files_search')->canApply(fn() => false),
            Switcher::make('Строгий поиск', 'strict_search')->canApply(fn() => false)
                ->hint('Точная последовательность слов друг за другом.')
                ->default(1),
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->empty();
            //->only(Action::VIEW);
    }


    protected function search(): array
    {
        return ['purchase_number'];
    }
}
