<?php

namespace Home\Cloudee\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class CloudeeServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'cloudee');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        #-- create a macro for the Http facade to fluently make requests to nextcloud
        Http::macro('nextcloud', function(){
            return Http::withBasicAuth(config('cloudee.nextcloud.user'), config('cloudee.nextcloud.password'))
                ->withHeaders(array("OCS-APIRequest" => "true"))
                ->baseUrl(config('cloudee.nextcloud.url'));
        });

        $this->loadRoutesFrom(__DIR__.'/../../routes/cloudee.php');
    }
}
