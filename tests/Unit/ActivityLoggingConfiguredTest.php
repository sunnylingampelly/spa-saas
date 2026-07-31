<?php

namespace Tests\Unit;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Employees\Models\EmployeeAttendance;
use App\Domain\Employees\Models\EmployeeLeave;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Services\Models\Service;
use App\Domain\Services\Models\ServiceCategory;
use App\Domain\Tenancy\Models\Spa;
use App\Domain\Tenancy\Models\SpaSetting;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * LogOptions::defaults() starts with an empty logAttributes array — a model that only calls
 * ->logOnlyDirty()->dontSubmitEmptyLogs() without ->logAll()/->logOnly([...]) silently logs
 * NOTHING, ever, because the computed change-set is always empty. This bit every LogsActivity
 * model except User for the entire project until it was caught here. This test exists so that
 * a future model added with the same incomplete config fails loudly instead of silently.
 */
class ActivityLoggingConfiguredTest extends TestCase
{
    public static function loggedModels(): array
    {
        return [
            [Spa::class], [SpaSetting::class], [Employee::class], [EmployeeAttendance::class],
            [EmployeeLeave::class], [Service::class], [ServiceCategory::class], [Customer::class],
            [Invoice::class], [Payment::class], [Expense::class], [Appointment::class], [User::class],
        ];
    }

    #[DataProvider('loggedModels')]
    public function test_model_actually_logs_attributes_not_just_dirty_tracking(string $modelClass): void
    {
        $options = (new $modelClass())->getActivitylogOptions();

        $this->assertNotEmpty(
            $options->logAttributes,
            "{$modelClass}::getActivitylogOptions() has an empty logAttributes list — add ->logAll() or ->logOnly([...]), or every activity log for this model will be silently discarded.",
        );
    }
}
