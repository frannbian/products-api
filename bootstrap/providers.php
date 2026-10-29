<?php

use App\Providers\AppServiceProvider;
use App\Providers\CatalogServiceProvider;
use App\Providers\CurrencyServiceProvider;
use App\Providers\IdentityServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    CurrencyServiceProvider::class,
    IdentityServiceProvider::class,
];
