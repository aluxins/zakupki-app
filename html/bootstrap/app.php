<?php

use App\Http\Services\Schedules\AIQuestion;
use App\Http\Services\Schedules\ImportPurchases;
use App\Http\Services\Schedules\ImportPurchasesGovRu;
use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\MoonShineServiceProvider;
use App\Providers\OpenRouterServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        RouteServiceProvider::class,
        AppServiceProvider::class,
        MoonShineServiceProvider::class,
        OpenRouterServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->call(new ImportPurchases('fz223'))  ->hourlyAt([ 0, 15, 30, 45]);
        $schedule->call(new ImportPurchases('fz44'))   ->hourlyAt([ 5, 20, 35, 50]);
        $schedule->call(new ImportPurchasesGovRu())           ->hourlyAt([10, 25, 40, 55]);
        $schedule->command('queue:prune-failed --hours=168')->daily();
        $schedule->call(new AIQuestion('getDeliveryDate'))->daily()->name('getDeliveryDate');

        //php artisan schedule:test --name getDeliveryDate
    })
    ->create();
