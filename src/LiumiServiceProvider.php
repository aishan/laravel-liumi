<?php

namespace Aishan\LaravelLiumi;

use Illuminate\Support\ServiceProvider;

class LiumiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/liumi.php'=>config_path('liumi.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('liumi',function($app){
            return new \Aishan\LaravelLiumi\Liumi;
        });
    }


}
