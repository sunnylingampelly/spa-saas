<?php

use App\Providers\AppointmentsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\BillingServiceProvider;
use App\Providers\CustomersServiceProvider;
use App\Providers\EmployeesServiceProvider;
use App\Providers\ExpensesServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\MarketingServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\ServicesServiceProvider;
use App\Providers\SupportServiceProvider;
use App\Providers\TenancyServiceProvider;
use App\Providers\WhatsAppServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    RepositoryServiceProvider::class,
    TenancyServiceProvider::class,
    EmployeesServiceProvider::class,
    ServicesServiceProvider::class,
    CustomersServiceProvider::class,
    AppointmentsServiceProvider::class,
    BillingServiceProvider::class,
    ExpensesServiceProvider::class,
    SupportServiceProvider::class,
    MarketingServiceProvider::class,
    WhatsAppServiceProvider::class,
];
