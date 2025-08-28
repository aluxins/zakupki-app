<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
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
use App\MoonShine\Resources\LogMessageResource;

final class MoonShineLayout extends AppLayout
{

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
                        Config::get('app.purchases.show_purchases_last_days'),
                    )->toDateString();
            }),
            MenuItem::make('Файлы', FileResource::class),
            MenuItem::make('Запросы ИИ', AIMessageResource::class),
            MenuItem::make('Логи', LogMessageResource::class),
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

        // Установка цветов фона
        $colorManager->content('22, 24, 28');

        // Установка цветов контента
        $colorManager->background('29, 31, 37');

        // Основной цвет
        $colorManager->primary('40, 67, 143');
        // Вторичный цвет
        $colorManager->secondary('53, 69, 103');

        // Настройка компонентов интерфейса
        $colorManager->tableRow('35, 38, 45'); // Строки таблицы
        $colorManager->borders('53, 69, 103'); // Границы
        $colorManager->dropdowns('48, 61, 93'); // Выпадающие списки
        $colorManager->buttons('83, 103, 132'); // Кнопки
        $colorManager->dividers('74, 90, 121'); // Разделители
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
