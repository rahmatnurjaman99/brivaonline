<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline;

use Illuminate\Support\ServiceProvider;
use RahmatNurjaman99\BrivaOnline\Clients\SnapClient;
use RahmatNurjaman99\BrivaOnline\Clients\WsdlClient;
use RahmatNurjaman99\BrivaOnline\Contracts\InquiryResolver;
use RahmatNurjaman99\BrivaOnline\Contracts\PaymentResolver;
use RahmatNurjaman99\BrivaOnline\Repositories\InquiryRepository;
use RahmatNurjaman99\BrivaOnline\Repositories\TokenRepository;
use RahmatNurjaman99\BrivaOnline\Resolvers\WsdlInquiryResolver;
use RahmatNurjaman99\BrivaOnline\Resolvers\WsdlPaymentResolver;

class BrivaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/briva.php', 'briva');

        $this->app->singleton(TokenRepository::class);
        $this->app->singleton(InquiryRepository::class);
        $this->app->singleton(SnapClient::class);
        $this->app->singleton(WsdlClient::class);
        $resolverClass = (string) config('briva.inquiry_resolver', WsdlInquiryResolver::class);
        $this->app->bind(InquiryResolver::class, $resolverClass);
        $paymentResolverClass = (string) config('briva.payment_resolver', WsdlPaymentResolver::class);
        $this->app->bind(PaymentResolver::class, $paymentResolverClass);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/briva.php' => config_path('briva.php'),
        ], 'briva-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'briva-migrations');

        if (config('briva.routes.enabled', true)) {
            $prefix = (string) config('briva.routes.prefix', '');
            if ($prefix !== '') {
                $this->app['router']->group(['prefix' => $prefix], function (): void {
                    $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
                });
            } else {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            }
        }
    }
}
