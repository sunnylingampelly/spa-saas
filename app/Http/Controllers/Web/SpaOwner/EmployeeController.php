<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Employees\Actions\CreateEmployeeAction;
use App\Domain\Employees\Actions\DeleteEmployeeAction;
use App\Domain\Employees\Actions\ToggleEmployeeStatusAction;
use App\Domain\Employees\Actions\UpdateEmployeeAction;
use App\Domain\Employees\DTOs\CreateEmployeeData;
use App\Domain\Employees\Models\Employee;
use App\Domain\Employees\Repositories\EmployeeAttendanceRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Employees/Index', [
            'employees' => Employee::query()->latest()->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Employees/Create');
    }

    public function store(Request $request, CreateEmployeeAction $action): RedirectResponse
    {
        $data = $this->validated($request);

        $employee = $action->execute(new CreateEmployeeData(
            name: $data['name'],
            gender: $data['gender'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            addressLine1: $data['address_line_1'] ?? null,
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            pincode: $data['pincode'] ?? null,
            emergencyContactName: $data['emergency_contact_name'] ?? null,
            emergencyContactPhone: $data['emergency_contact_phone'] ?? null,
            joiningDate: $data['joining_date'] ?? null,
            department: $data['department'] ?? null,
            designation: $data['designation'] ?? null,
            salary: $data['salary'] ?? null,
            commissionType: $data['commission_type'] ?? 'percentage',
            commissionValue: $data['commission_value'] ?? 0,
            experienceYears: $data['experience_years'] ?? 0,
            skills: $data['skills'] ?? [],
            specializations: $data['specializations'] ?? [],
            notes: $data['notes'] ?? null,
        ));

        return redirect()->route('employees.show', $employee)->with('success', 'Employee added.');
    }

    public function show(Employee $employee, EmployeeAttendanceRepositoryInterface $attendanceRepository): Response
    {
        $this->authorize('view', $employee);

        return Inertia::render('Employees/Show', [
            'employee' => $employee->load([
                'attendances' => fn ($q) => $q->latest('attendance_date')->limit(30),
                'leaves' => fn ($q) => $q->latest('start_date')->limit(20),
            ]),
            'attendanceSummary' => $attendanceRepository->summaryForEmployee($employee->id, now()->subDays(30), now()),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        return Inertia::render('Employees/Edit', ['employee' => $employee]);
    }

    public function update(Request $request, Employee $employee, UpdateEmployeeAction $action): RedirectResponse
    {
        $this->authorize('update', $employee);

        $action->execute($employee, $this->validated($request));

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee, DeleteEmployeeAction $action): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $action->execute($employee);

        return redirect()->route('employees.index')->with('success', 'Employee removed.');
    }

    public function toggleStatus(Request $request, Employee $employee, ToggleEmployeeStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate(['status' => ['required', 'in:active,inactive,on_leave']]);

        $action->execute($employee, $data['status']);

        return back()->with('success', 'Employee status updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'joining_date' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'commission_type' => ['nullable', 'in:percentage,flat'],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'skills' => ['nullable', 'array'],
            'specializations' => ['nullable', 'array'],
            'performance_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'performance_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
