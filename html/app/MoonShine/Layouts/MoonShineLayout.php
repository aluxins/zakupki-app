<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use Illuminate\Support\Carbon;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Laravel\Components\Layout\{Locales, Notifications, Profile, Search};
use MoonShine\UI\Components\{Breadcrumbs,
    Components,
    Layout\Flash,
    Layout\Div,
    Layout\Body,
    Layout\Burger,
    Layout\Content,
    Layout\Footer,
    Layout\Head,
    Layout\Favicon,
    Layout\Assets,
    Layout\Meta,
    Layout\Header,
    Layout\Html,
    Layout\Layout,
    Layout\Logo,
    Layout\Menu,
    Layout\Sidebar,
    Layout\ThemeSwitcher,
    Layout\TopBar,
    Layout\Wrapper,
    When};
use App\MoonShine\Resources\UserResource;
use MoonShine\MenuManager\MenuItem;
use App\MoonShine\Resources\PurchaseResource;
use App\MoonShine\Resources\FileResource;
use App\MoonShine\Resources\AIMessageResource;

final class MoonShineLayout extends AppLayout
{
    const SHOW_PURCHASES_LAST_DAYS = 7;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
    {

        return [
            MenuItem::make('Закупки', function () {
                $url = MenuItem::make('Закупки', PurchaseResource::class)->getUrl();
                return $url . '?filter[published_at][from]=' . Carbon::now()->subDays(
                        self::SHOW_PURCHASES_LAST_DAYS
                    )->toDateString();
            }),
            MenuItem::make('Файлы', FileResource::class),
            MenuItem::make('Запросы ИИ', AIMessageResource::class),
            MenuItem::make('Пользователи', UserResource::class),
            ...parent::menu(),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }

    public function build(): Layout
    {
        return parent::build();
    }

    protected function getFooterCopyright(): string
    {
        return \sprintf(
            <<<'HTML'
                &copy; 2012-%d Апкау
                <a href="https://apkau.ru/"
                    class="font-semibold text-primary hover:text-secondary"
                    target="_blank"
                >
                    apkau.ru
                </a>
                HTML,
            now()->year,
        );
    }
}
