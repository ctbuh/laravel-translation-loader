<?php

namespace Spatie\TranslationLoader;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider as IlluminateTranslationServiceProvider;
use Spatie\TranslationLoader\Commands\FindTranslations;

class TranslationServiceProvider extends IlluminateTranslationServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        parent::register();

        $this->app->singleton('translator', function ($app) {

            $loader = $app['translation.loader'];
            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];
            $trans = new Translator($loader, $locale);
            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/translation-loader.php', 'translation-loader');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole() && !str_contains($this->app->version(), 'Lumen')) {
            $this->publishes([
                __DIR__ . '/../config/translation-loader.php' => config_path('translation-loader.php'),
            ], 'config');

            if (!class_exists('CreateLanguageLinesTable')) {
                $timestamp = date('Y_m_d_His', time());

                $this->publishes([
                    __DIR__ . '/../database/migrations/create_language_lines_table.php.stub' => database_path('migrations/' . $timestamp . '_create_language_lines_table.php'),
                ], 'migrations');
            }
        }

        $this->registerCommands();
    }

    // https://laravel.com/docs/5.7/packages#commands
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FindTranslations::class
            ]);
        }
    }

    /**
     * Register the translation line loader. This method registers a
     * `TranslationLoaderManager` instead of a simple `FileLoader` as the
     * applications `translation.loader` instance.
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            $class = config('translation-loader.translation_manager');

            return new $class($app['files'], $app['path.lang']);
        });
    }
}
