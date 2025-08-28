<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\LogMessage;
use App\MoonShine\Pages\LogMessage\LogMessageIndexPage;
use App\MoonShine\Pages\LogMessage\LogMessageFormPage;
use App\MoonShine\Pages\LogMessage\LogMessageDetailPage;

use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\Enums\ClickAction;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<LogMessage, LogMessageIndexPage, LogMessageFormPage, LogMessageDetailPage>
 */
class LogMessageResource extends ModelResource
{
    protected string $model = LogMessage::class;

    protected string $title = 'Логи';

    protected bool $detailInModal = true;

    protected ?ClickAction $clickAction = ClickAction::DETAIL;

    /**
     * @return list<Page>
     */
    protected function pages(): array
    {
        return [
            LogMessageIndexPage::class,
            //LogMessageFormPage::class,
            LogMessageDetailPage::class,
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Level Name', 'level_name'),
            Text::make('Level', 'level'),
            Text::make('Message', 'message'),
            Date::make('Date', 'logged_at')->sortable(),
            Text::make('Context', 'context')->changePreview(
                fn($value, Text $ctx) => mb_substr(
                    json_encode($ctx->getData()->getOriginal()->context->toArray()),
                    0, 50
                )
            ),
            Text::make('Extra', 'extra')->changePreview(
                fn($value, Text $ctx) => mb_substr(
                    json_encode($ctx->getData()->getOriginal()->extra->toArray()),
                    0, 50
                )
            ),
        ];
    }

    /**
     * @param LogMessage $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    protected function rules(mixed $item): array
    {
        return [];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->only(Action::VIEW);
    }
}
