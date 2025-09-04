<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Helpers\CodesSubjects;
use Closure;
use Illuminate\Database\Eloquent\Model;
use App\Models\AiMessage;
use App\MoonShine\Pages\AIMessage\AIMessageIndexPage;
use App\MoonShine\Pages\AIMessage\AIMessageFormPage;
use App\MoonShine\Pages\AIMessage\AIMessageDetailPage;

use Illuminate\Support\Carbon;
use MoonShine\Contracts\UI\Collection\TableRowsContract;
use MoonShine\Contracts\UI\TableRowContract;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\ListOf;
use MoonShine\UI\Collections\TableRows;
use MoonShine\UI\Components\Table\TableRow;
use MoonShine\UI\Components\Table\TableTh;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;

/**
 * @extends ModelResource<AiMessage, AIMessageIndexPage, AIMessageFormPage, AIMessageDetailPage>
 */
class AIMessageResource extends ModelResource
{
    protected string $model = AiMessage::class;

    protected string $title = 'AIMessages';

    protected bool $createInModal = true;

    protected bool $editInModal = true;

    protected bool $detailInModal = true;

    /**
     * @return list<Page>
     */
    protected function pages(): array
    {
        return [
            AIMessageIndexPage::class,
            //AIMessageFormPage::class,
            AIMessageDetailPage::class,
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Url::make('Закупка')
                ->changePreview(function ($value, Url $ctx) {
                    $purchase = $ctx->getData()->getOriginal()->purchase;
                    $purchase_number = $purchase->purchase_number ?? '';
                    return '<a target="_blank" href="' . toPage(
                            page: IndexPage::class,
                            resource: PurchaseResource::class
                        ) . '?search=' . $purchase_number . '">' . $purchase_number . '</a>';
                }),
            Text::make('Метод', 'key')->changePreview(
                fn($value, Text $ctx) => $this->nameMethod(explode('_', $ctx->getData()->getOriginal()->key)[0] ?? '')
            ),
            //Text::make('Запрос', 'question'),
            //Text::make('Ответ', 'response'),
            Date::make('Создано', 'created_at')
                ->format('d.m.Y H:i:s')
                ->sortable(),
            Date::make('Изменено', 'updated_at')
                ->format('d.m.Y H:i:s')
                ->sortable(),
            Text::make('Описание', 'object_info')
                ->changePreview(function ($value, Text $ctx) {
                    $purchase = $ctx->getData()->getOriginal()->purchase;
                    return implode(' - ', [
                        Carbon::parse($purchase->delivered_at ?? '')->format('d.m.Y'),
                        CodesSubjects::get()->one($purchase->region ?? 0),
                        $purchase->object_info ?? '',
                    ]);
                }),
        ];
    }

    /**
     * Переопределение тела таблицы. Строкам TR назначить кастомное представление. Строка разбивается на две строки.
     * @return TableRowsContract|Closure|null
     */
    protected function tbody(): null|TableRowsContract|Closure
    {
        return static function (TableRowsContract $default) {
            foreach ($default as $item) {
                $item->onBeforeRender(function (TableRow $field) {
                    return $field->customView('admin.components.table-double-row')->customAttributes(
                        ['data-double-row' => 'object_info']
                    );
                });
            }
            return $default;
        };
    }

    /**
     * Переопределение шапки таблицы. Скрытие столбца object_info.
     * @return TableRowsContract|Closure|null
     */
    protected function thead(): null|TableRowsContract|Closure
    {
        return static function (TableRowContract $default) {
            foreach ($default->getCells() as $item) {
                $item->onBeforeRender(function (TableTh $field) {
                    if ($field->getAttribute('data-column-selection') == 'object_info') {
                        $field->class('hidden');
                    }
                    return $field;
                }
                );
            }
            return TableRows::make([$default]);
        };
    }

    private function nameMethod(string $method): string
    {
        return match ($method) {
            'getDeliveryDate' => 'Срок поставки',
            default => $method
        };
    }

    /**
     * @param AiMessage $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    protected function rules(mixed $item): array
    {
        return [];
    }

    protected function search(): array
    {
        return ['key'];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->only(Action::VIEW);
    }
}
