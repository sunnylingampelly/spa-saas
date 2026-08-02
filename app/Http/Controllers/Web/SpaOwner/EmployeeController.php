<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Employees\Actions\CreateEmployeeAction;
use App\Domain\Employees\Actions\DeleteEmployeeAction;
use App\Domain\Employees\Actions\ExportEmployeesAction;
use App\Domain\Employees\Actions\ImportEmployeesAction;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function export(ExportEmployeesAction $action): StreamedResponse
    {
        return $action->execute();
    }

    public function importTemplate(ExportEmployeesAction $action): StreamedResponse
    {
        return $action->template();
    }

    public function import(Request $request, ImportEmployeesAction $action): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $result = $action->execute($request->file('file'));

        return back()
            ->with('success', "Imported {$result->importedCount} employee(s).")
            ->with('import_errors', $result->rowErrors);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            ...ImportEmployeesAction::validationRules(),
            'skills' => ['nullable', 'array'],
            'specializations' => ['nullable', 'array'],
            'performance_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'performance_notes' => ['nullable', 'string'],
        ]);
    }
}
