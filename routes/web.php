<?php

use App\Http\Controllers\Web\Marketing\PricingController;
use App\Http\Controllers\Web\Onboarding\CreateSpaController;
use App\Http\Controllers\Web\SpaOwner\AppointmentController;
use App\Http\Controllers\Web\SpaOwner\CommissionReportController;
use App\Http\Controllers\Web\SpaOwner\CustomerController;
use App\Http\Controllers\Web\SpaOwner\CustomerWalletController;
use App\Http\Controllers\Web\SpaOwner\DashboardController;
use App\Http\Controllers\Web\SpaOwner\EmployeeAttendanceController;
use App\Http\Controllers\Web\SpaOwner\EmployeeController;
use App\Http\Controllers\Web\SpaOwner\EmployeeLeaveController;
use App\Http\Controllers\Web\SpaOwner\ExpenseController;
use App\Http\Controllers\Web\SpaOwner\InvoiceController;
use App\Http\Controllers\Web\SpaOwner\InvoiceDeliveryController;
use App\Http\Controllers\Web\SpaOwner\InvoicePaymentController;
use App\Http\Controllers\Web\Public\InvoicePaymentController as PublicInvoicePaymentController;
use App\Http\Controllers\Web\SpaOwner\ServiceCategoryController;
use App\Http\Controllers\Web\SpaOwner\ServiceController;
use App\Http\Controllers\Web\SpaOwner\SpaProfileController;
use App\Http\Controllers\Web\SpaOwner\SubscriptionCheckoutController;
use App\Http\Controllers\Web\SpaOwner\SubscriptionController;
use App\Http\Controllers\Web\SpaOwner\SupportTicketController;
use App\Http\Controllers\Web\SuperAdmin\ActivityLogController;
use App\Http\Controllers\Web\SuperAdmin\AdminUserController;
use App\Http\Controllers\Web\SuperAdmin\AnnouncementController;
use App\Http\Controllers\Web\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Web\SuperAdmin\PaymentController as SuperAdminPaymentController;
use App\Http\Controllers\Web\SuperAdmin\PendingPaymentController;
use App\Http\Controllers\Web\SuperAdmin\SpaController as SuperAdminSpaController;
use App\Http\Controllers\Web\SuperAdmin\SupportTicketController as SuperAdminSupportTicketController;
use App\Http\Controllers\Web\SuperAdmin\TwoFactorSetupController;
use App\Http\Controllers\Web\StopImpersonationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->hasRole('super_admin') ? 'admin.dashboard' : 'dashboard');
    }

    return Inertia::render('Marketing/Landing', [
        'plans' => config('subscriptions.plans'),
        'trialDays' => config('subscriptions.trial_days'),
    ]);
})->name('home');

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

// Fully public — reached only via an unguessable Invoice::public_token. No auth, no
// spa.context, since a spa's customer never has a login on this platform.
Route::prefix('pay')->name('public.invoices.')->group(function () {
    Route::get('/{invoice:public_token}', [PublicInvoicePaymentController::class, 'show'])->name('show');

    Route::middleware('throttle:financial')->group(function () {
        Route::post('/{invoice:public_token}/razorpay/order', [PublicInvoicePaymentController::class, 'createOrder'])->name('razorpay.order');
        Route::post('/{invoice:public_token}/razorpay/verify', [PublicInvoicePaymentController::class, 'verify'])->name('razorpay.verify');
    });
});

Route::middleware('auth')->group(function () {
    // Onboarding must NOT go through spa.context — a user with no spa yet would
    // be redirected back here in a loop.
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/create-spa', [CreateSpaController::class, 'show'])->name('create-spa.show');
        Route::post('/create-spa', [CreateSpaController::class, 'store'])->name('create-spa.store');
    });

    Route::get('/spa/switch', fn () => Inertia::render('Onboarding/SwitchSpa', [
        'spas' => auth()->user()->spas,
    ]))->name('spa.switch');

    Route::post('/spa/switch', function (\Illuminate\Http\Request $request) {
        $spaId = $request->validate(['spa_id' => ['required', 'integer']])['spa_id'];

        abort_unless($request->user()->spas()->where('spas.id', $spaId)->exists(), 403);

        $request->session()->put('current_spa_id', $spaId);

        return redirect()->route('dashboard');
    })->name('spa.switch.store');

    // Reachable regardless of role/spa-context — not behind spa.context, since that's the
    // exact middleware redirecting a suspended spa's owner here (avoids a redirect loop).
    Route::get('/suspended', fn () => Inertia::render('Suspended'))->name('suspended');

    // Reachable regardless of role — mid-impersonation the acting user has the spa_owner
    // role, not super_admin, so this can't live inside the admin.* route group.
    Route::post('/stop-impersonating', [StopImpersonationController::class, 'store'])->name('stop-impersonating');
});

// Always reachable once a spa exists, even if the subscription has lapsed —
// an owner locked out by EnsureSubscriptionActive still needs to reach these.
Route::middleware(['auth', 'role:spa_owner', 'spa.context', 'throttle:120,1'])->group(function () {
    Route::get('/spa/profile', [SpaProfileController::class, 'show'])->name('spa.profile.show');
    Route::put('/spa/profile', [SpaProfileController::class, 'update'])->name('spa.profile.update');

    Route::middleware('throttle:financial')->group(function () {
        Route::put('/spa/payment-settings', [SpaProfileController::class, 'updatePaymentSettings'])->name('spa.payment-settings.update');
        Route::delete('/spa/payment-settings', [SpaProfileController::class, 'disconnectPaymentSettings'])->name('spa.payment-settings.disconnect');
    });

    Route::get('/subscription', [SubscriptionController::class, 'show'])->name('subscription.show');

    Route::middleware('throttle:financial')->group(function () {
        Route::post('/subscription/razorpay/order', [SubscriptionCheckoutController::class, 'razorpayOrder'])->name('subscription.razorpay.order');
        Route::post('/subscription/razorpay/verify', [SubscriptionCheckoutController::class, 'razorpayVerify'])->name('subscription.razorpay.verify');
        Route::post('/subscription/manual', [SubscriptionCheckoutController::class, 'manualSubmit'])->name('subscription.manual');
    });

    Route::get('/support/tickets', [SupportTicketController::class, 'index'])->name('support.tickets.index');
    Route::get('/support/tickets/create', [SupportTicketController::class, 'create'])->name('support.tickets.create');
    Route::post('/support/tickets', [SupportTicketController::class, 'store'])->name('support.tickets.store');
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.tickets.show');
    Route::post('/support/tickets/{ticket}/messages', [SupportTicketController::class, 'reply'])->name('support.tickets.reply');
});

Route::middleware(['auth', 'role:spa_owner', 'spa.context', 'subscription.active', 'throttle:120,1'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

    Route::resource('employees', EmployeeController::class);
    Route::patch('/employees/{employee}/status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');
    Route::post('/employees/attendance', [EmployeeAttendanceController::class, 'store'])->name('employees.attendance.store');
    Route::post('/employees/{employee}/leaves', [EmployeeLeaveController::class, 'store'])->name('employees.leaves.store');

    Route::resource('services', ServiceController::class)->except(['show']);
    Route::patch('/services/{service}/status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');
    Route::post('/services/seed-sample-catalog', [ServiceController::class, 'seedSampleCatalog'])->name('services.seed-sample-catalog');
    Route::resource('service-categories', ServiceCategoryController::class)->only(['store', 'update', 'destroy']);

    // Must come before the resource route below — otherwise "quick-create" is swallowed as a {customer} id.
    Route::post('/customers/quick-create', [CustomerController::class, 'quickCreate'])->name('customers.quick-create');

    Route::resource('customers', CustomerController::class);
    Route::post('/customers/{customer}/wallet', [CustomerWalletController::class, 'store'])
        ->middleware('throttle:financial')->name('customers.wallet.store');

    Route::resource('appointments', AppointmentController::class)->except(['show']);
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.update-status');
    Route::patch('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');

    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('/invoices/{invoice}/payments', [InvoicePaymentController::class, 'store'])
        ->middleware('throttle:financial')->name('invoices.payments.store');
    Route::post('/invoices/{invoice}/refund', [InvoicePaymentController::class, 'refund'])
        ->middleware('throttle:financial')->name('invoices.refund');
    Route::get('/invoices/{invoice}/download', [InvoiceDeliveryController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/email', [InvoiceDeliveryController::class, 'email'])->name('invoices.email');

    Route::get('/reports/commissions', [CommissionReportController::class, 'index'])->name('reports.commissions');

    Route::resource('expenses', ExpenseController::class)->except(['show']);
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    // Not behind two_factor.required — a super admin without 2FA yet must still be able to reach this.
    Route::get('/two-factor-setup', [TwoFactorSetupController::class, 'show'])->name('two-factor.setup');

    Route::middleware('two_factor.required')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'show'])->name('dashboard');

        Route::get('/spas', [SuperAdminSpaController::class, 'index'])->name('spas.index');
        Route::get('/spas/{spa}', [SuperAdminSpaController::class, 'show'])->name('spas.show');
        Route::patch('/spas/{spa}/status', [SuperAdminSpaController::class, 'updateStatus'])->name('spas.update-status');
        Route::post('/spas/{spa}/impersonate', [SuperAdminSpaController::class, 'impersonate'])->name('spas.impersonate');
        Route::patch('/spas/{spa}/subscription', [SuperAdminSpaController::class, 'updateSubscription'])->name('spas.subscription.update');
        Route::patch('/spas/{spa}/owner', [SuperAdminSpaController::class, 'updateOwner'])->name('spas.owner.update');
        Route::patch('/spas/{spa}/owner/status', [SuperAdminSpaController::class, 'toggleOwnerStatus'])->name('spas.owner.toggle-status');
        Route::delete('/spas/{spa}/owner', [SuperAdminSpaController::class, 'deleteOwner'])->name('spas.owner.delete');

        Route::get('/payments', [SuperAdminPaymentController::class, 'index'])->name('payments.index');

        Route::get('/pending-payments', [PendingPaymentController::class, 'index'])->name('pending-payments.index');
        Route::post('/pending-payments/{payment}/confirm', [PendingPaymentController::class, 'confirm'])->name('pending-payments.confirm');

        Route::get('/admins', [AdminUserController::class, 'index'])->name('admins.index');
        Route::post('/admins', [AdminUserController::class, 'store'])->name('admins.store');

        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');

        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::patch('/announcements/{announcement}/deactivate', [AnnouncementController::class, 'deactivate'])->name('announcements.deactivate');

        Route::get('/support-tickets', [SuperAdminSupportTicketController::class, 'index'])->name('support-tickets.index');
        Route::get('/support-tickets/{ticket}', [SuperAdminSupportTicketController::class, 'show'])->name('support-tickets.show');
        Route::post('/support-tickets/{ticket}/messages', [SuperAdminSupportTicketController::class, 'reply'])->name('support-tickets.reply');
        Route::patch('/support-tickets/{ticket}/status', [SuperAdminSupportTicketController::class, 'updateStatus'])->name('support-tickets.update-status');
    });
});
