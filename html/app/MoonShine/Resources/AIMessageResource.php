<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\AiMessage;
use App\MoonShine\Pages\AIMessage\AIMessageIndexPage;
use App\MoonShine\Pages\AIMessage\AIMessageFormPage;
use App\MoonShine\Pages\AIMessage\AIMessageDetailPage;

use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\ListOf;
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
        ];
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
