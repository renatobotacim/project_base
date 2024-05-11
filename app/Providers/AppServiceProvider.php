<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('App\Repositories\AuthRepositoryInterface', 'App\Repositories\AuthRepositoryEloquent');
        $this->app->bind('App\Repositories\CategoryRepositoryInterface', 'App\Repositories\CategoryRepositoryEloquent');
        $this->app->bind('App\Repositories\EventRepositoryInterface', 'App\Repositories\EventRepositoryEloquent');
        $this->app->bind('App\Repositories\CityRepositoryInterface', 'App\Repositories\CityRepositoryEloquent');
        $this->app->bind('App\Repositories\AddressRepositoryInterface', 'App\Repositories\AddressRepositoryEloquent');
        $this->app->bind('App\Repositories\ProducerRepositoryInterface', 'App\Repositories\ProducerRepositoryEloquent');
        $this->app->bind('App\Repositories\OwnerRepositoryInterface', 'App\Repositories\OwnerRepositoryEloquent');
        $this->app->bind('App\Repositories\MapsRepositoryInterface', 'App\Repositories\MapsRepositoryEloquent');
        $this->app->bind('App\Repositories\SectorRepositoryInterface', 'App\Repositories\SectorRepositoryEloquent');
        $this->app->bind('App\Repositories\TicketEventRepositoryInterface', 'App\Repositories\TicketEventRepositoryEloquent');
        $this->app->bind('App\Repositories\BatchRepositoryInterface', 'App\Repositories\BatchRepositoryEloquent');
        $this->app->bind('App\Repositories\BagRepositoryInterface', 'App\Repositories\BagRepositoryEloquent');
        $this->app->bind('App\Repositories\TicketRepositoryInterface', 'App\Repositories\TicketRepositoryEloquent');
        $this->app->bind('App\Repositories\SaleRepositoryInterface', 'App\Repositories\SaleRepositoryEloquent');
        $this->app->bind('App\Repositories\CouponRepositoryInterface', 'App\Repositories\CouponRepositoryEloquent');
        $this->app->bind('App\Repositories\HitRepositoryInterface', 'App\Repositories\HitRepositoryEloquent');
        $this->app->bind('App\Repositories\WithdrawalRepositoryInterface', 'App\Repositories\WithdrawalRepositoryEloquent');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
