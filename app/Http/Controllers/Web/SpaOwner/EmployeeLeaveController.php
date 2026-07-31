<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Employees\Actions\RecordLeaveAction;
use App\Domain\Employees\DTOs\RecordLeaveData;
use App\Domain\Employees\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeLeaveController extends Controller
{
    public function store(Request $request, Employee $employee, RecordLeaveAction $action): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'leave_type' => ['required', 'in:sick,casual,paid,unpaid,other'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ]);

        $action->execute(new RecordLeaveData(
            employeeId: $employee->id,
            leaveType: $data['leave_type'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            reason: $data['reason'] ?? null,
        ));

        return back()->with('success', 'Leave recorded.');
    }
}
