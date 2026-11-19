<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Detecta automáticamente la URL base real (dominio o subcarpeta, p. ej.
        // http://localhost/mi-sistema/public) para que Livewire cargue su JS y
        // envíe sus peticiones a la ruta correcta sin depender de APP_URL.
        if (! $this->app->runningInConsole()) {
            $baseUrl = rtrim(request()->getSchemeAndHttpHost() . request()->getBaseUrl(), '/');

            config([
                'livewire.asset_url' => config('livewire.asset_url') ?: $baseUrl,
                'livewire.app_url'   => config('livewire.app_url') ?: $baseUrl,
            ]);
        }

        //DB::listen(function ($query) {
        // Log::info($query->sql, $query->bindings, $query->time);
        //Log::info($query->sql, $query->bindings);     // the query being executed
        //Log::info($query->time);
        //});
    }
}
