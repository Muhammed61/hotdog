<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;

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
        // Ayarları tüm view'larda kullanılabilir hale getir
        View::composer('*', function ($view) {
            try {
                $settings = Setting::pluck('value', 'key')->toArray();
                $view->with('settings', $settings);
            } catch (\Exception $e) {
                // Veritabanı henüz hazır değilse boş array döndür
                $view->with('settings', []);
            }
        });
    }
}
