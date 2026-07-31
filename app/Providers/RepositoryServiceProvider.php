<?php

namespace App\Providers;

use App\Domain\Billing\Repositories\CommissionRepositoryInterface;
use App\Domain\Billing\Repositories\EloquentCommissionRepository;
use App\Domain\Customers\Repositories\CustomerHistoryRepositoryInterface;
use App\Domain\Customers\Repositories\EloquentCustomerHistoryRepository;
use App\Domain\Employees\Repositories\EloquentEmployeeAttendanceRepository;
use App\Domain\Employees\Repositories\EmployeeAttendanceRepositoryInterface;
use App\Domain\Tenancy\Repositories\EloquentSpaRepository;
use App\Domain\Tenancy\Repositories\SpaRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SpaRepositoryInterface::class, EloquentSpaRepository::class);
        $this->app->bind(EmployeeAttendanceRepositoryInterface::class, EloquentEmployeeAttendanceRepository::class);
        $this->app->bind(CustomerHistoryRepositoryInterface::class, EloquentCustomerHistoryRepository::class);
        $this->app->bind(CommissionRepositoryInterface::class, EloquentCommissionRepository::class);
    }
}
