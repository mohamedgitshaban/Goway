<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\BayMobService;
use App\Services\Payments\PaymentGatewayFactoryInterface;
use App\Services\Payments\PaymentGatewayFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind payment gateway interface to the BayMob implementation by default
        $this->app->bind(PaymentGatewayInterface::class, BayMobService::class);

        // Bind the payment gateway factory used to resolve gateways by payment method
        $this->app->bind(PaymentGatewayFactoryInterface::class, PaymentGatewayFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
