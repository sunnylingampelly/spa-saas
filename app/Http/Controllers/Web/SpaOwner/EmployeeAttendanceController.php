<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Employees\Actions\MarkAttendanceAction;
use App\Domain\Employees\DTOs\MarkAttendanceData;
use App\Domain\Employees\Models\Employee;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeAttendanceController extends Controller
{
    public function store(Request $request, MarkAttendanceAction $action): RedirectResponse
    {
        $data = $request->validate([
            'attendance_date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'entries.*.status' => ['required', 'in:present,absent,half_day,on_leave,holiday'],
            'entries.*.check_in' => ['nullable', 'date_format:H:i'],
            'entries.*.check_out' => ['nullable', 'date_format:H:i'],
            'entries.*.notes' => ['nullable', 'string'],
        ]);

        foreach ($data['entries'] as $entry) {
            $employee = Employee::findOrFail($entry['employee_id']);
            $this->authorize('update', $employee);
        }

        $action->execute(array_map(
            fn (array $entry) => new MarkAttendanceData(
                employeeId: $entry['employee_id'],
                attendanceDate: $data['attendance_date'],
                status: $entry['status'],
                checkIn: $entry['check_in'] ?? null,
                checkOut: $entry['check_out'] ?? null,
                notes: $entry['notes'] ?? null,
            ),
            $data['entries']
        ));

        return back()->with('success', 'Attendance recorded for '.Carbon::parse($data['attendance_date'])->format('d-m-Y').'.');
    }
}
