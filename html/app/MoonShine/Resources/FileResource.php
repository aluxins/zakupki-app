<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\File;
use App\MoonShine\Pages\File\FileIndexPage;
use App\MoonShine\Pages\File\FileFormPage;
use App\MoonShine\Pages\File\FileDetailPage;

use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\RelationRepeater;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;

/**
 * @extends ModelResource<File, FileIndexPage, FileFormPage, FileDetailPage>
 */
class FileResource extends ModelResource
{
    protected string $model = File::class;

    protected string $title = 'Files';

    protected bool $createInModal = true;

    protected bool $editInModal = true;

    protected bool $detailInModal = true;

    /**
     * @return list<Page>
     */
    protected function pages(): array
    {
        return [
            FileIndexPage::class,
            FileFormPage::class,
            FileDetailPage::class,
        ];
    }

    /**
     * @param File $item
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
            ID::make()->sortable(),

            Text::make(__('File name'), 'file_name'),

            Url::make(__('File url'), 'url'),
            Text::make('Номер закупки', 'purchase_number'),
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->only(Action::VIEW);
    }

    protected function search(): array
    {
        return ['purchase_number'];
    }
}
