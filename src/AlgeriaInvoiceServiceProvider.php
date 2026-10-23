<?php

namespace Djouamaah\AlgeriaInvoice;

use Djouamaah\AlgeriaInvoice\Numbering\Sequence;
use Djouamaah\AlgeriaInvoice\Rendering\Renderer;
use Illuminate\Support\ServiceProvider;

class AlgeriaInvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/algeria-invoice.php', 'algeria-invoice');

        $this->app->bind(Sequence::class, fn ($app) => new Sequence($app['db']->connection(), (array) $app['config']->get('algeria-invoice.numbering', [])));
        $this->app->bind(Renderer::class, fn ($app) => new Renderer($app['view'], (array) $app['config']->get('algeria-invoice', [])));
        $this->app->singleton(AlgeriaInvoice::class, fn ($app) => new AlgeriaInvoice(
            (array) $app['config']->get('algeria-invoice', []),
            $app->make(Sequence::class),
            $app->make(Renderer::class),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'algeria-invoice');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'algeria-invoice');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/algeria-invoice.php' => config_path('algeria-invoice.php')], 'algeria-invoice-config');
            $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'algeria-invoice-migrations');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/algeria-invoice')], 'algeria-invoice-views');
            $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/algeria-invoice')], 'algeria-invoice-lang');
        }
    }
}
