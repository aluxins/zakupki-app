<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\LogMessage;

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends DetailPage<ModelResource>
 */
class LogMessageDetailPage extends DetailPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Level Name', 'level_name'),
            Text::make('Level', 'level'),
            Text::make('Message', 'message'),
            Date::make('Date', 'logged_at')->sortable(),
            Text::make('Context', 'context')->changePreview(
                fn($value, Text $ctx) => print_r($ctx->getData()->getOriginal()->context->toArray(), true)
            )->customWrapperAttributes(['style' => 'white-space:pre-wrap']),
            Text::make('Extra', 'extra')->changePreview(
                fn($value, Text $ctx) => print_r($ctx->getData()->getOriginal()->extra->toArray(), true)
            )->customWrapperAttributes(['style' => 'white-space:pre-wrap']),
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
