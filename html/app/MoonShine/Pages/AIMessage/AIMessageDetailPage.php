<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\AIMessage;

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends DetailPage<ModelResource>
 */
class AIMessageDetailPage extends DetailPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Указатель', 'key'),
            Text::make('Запрос', 'question')
                ->changePreview(fn($value, Text $ctx) => $ctx->getData()->getOriginal()->question)
                ->customWrapperAttributes(['style' => 'white-space:pre-wrap']),
            Text::make('Ответ', 'response')
                ->changePreview(fn($value, Text $ctx) => print_r(
                    json_decode($ctx->getData()->getOriginal()->response, true),
                    true
                ))->customWrapperAttributes(['style' => 'white-space:pre-wrap']),
            Text::make('Создано', 'created_at'),
            Text::make('Изменено', 'updated_at'),
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
