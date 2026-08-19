<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ページ送りの見た目をBootstrapに合わせる（表示文言は
        // resources/views/vendor/pagination で日本語にしてある）。
        Paginator::useBootstrapFive();
    }
}
