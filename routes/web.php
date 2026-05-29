<?php

use App\Http\Controllers\Admin\Accounting\AccountController;
use App\Http\Controllers\Admin\Accounting\CashAccountController;
use App\Http\Controllers\Admin\Accounting\GeneralLedgerController;
use App\Http\Controllers\Admin\Accounting\JournalEntryController;
use App\Http\Controllers\Admin\Accounting\PaymentVoucherController;
use App\Http\Controllers\Admin\Accounting\PnlController;
use App\Http\Controllers\Admin\Accounting\ReceiptVoucherController;
use App\Http\Controllers\Admin\Accounting\TrialBalanceController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BookingAccommodationController;
use App\Http\Controllers\Admin\BookingCostController;
use App\Http\Controllers\Admin\BookingDocumentController;
use App\Http\Controllers\Admin\BookingPaymentController;
use App\Http\Controllers\Admin\BookingPilgrimController;
use App\Http\Controllers\Admin\BookingTransportationController;
use App\Http\Controllers\Admin\Catalog\AirlineController;
use App\Http\Controllers\Admin\Catalog\HotelController;
use App\Http\Controllers\Admin\Catalog\TransportProviderController;
use App\Http\Controllers\Admin\Catalog\VisaTypeController;
use App\Http\Controllers\Admin\Suppliers\SupplierAgingController;
use App\Http\Controllers\Admin\Suppliers\SupplierController;
use App\Http\Controllers\Admin\Suppliers\SupplierInvoiceController;
use App\Http\Controllers\Admin\Suppliers\SupplierStatementController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomesticBookingController;
use App\Http\Controllers\Admin\DomesticBookingCostController;
use App\Http\Controllers\Admin\DomesticBookingPaymentController;
use App\Http\Controllers\Admin\DomesticProgramController;
use App\Http\Controllers\Admin\DomesticReportController;
use App\Http\Controllers\Admin\Crm\LeadActivityController;
use App\Http\Controllers\Admin\Crm\LeadController;
use App\Http\Controllers\Admin\Crm\OpportunityController;
use App\Http\Controllers\Admin\Crm\WhatsAppChatController;
use App\Http\Controllers\Admin\Crm\WhatsAppMessageController;
use App\Http\Controllers\Admin\Crm\WhatsAppSettingController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\Hr\BranchController;
use App\Http\Controllers\Admin\Hr\DepartmentController;
use App\Http\Controllers\Admin\Hr\EmployeeController;
use App\Http\Controllers\Admin\Hr\Payroll\PayrollRunController;
use App\Http\Controllers\Admin\Hr\PositionController;
use App\Http\Controllers\Admin\ReportsHubController;
use App\Http\Controllers\Admin\ReligiousAlertController;
use App\Http\Controllers\Admin\ReligiousBookingController;
use App\Http\Controllers\Admin\ReligiousIntegrationController;
use App\Http\Controllers\Admin\ReligiousIntegrationsPageController;
use App\Http\Controllers\Admin\ReligiousPrintController;
use App\Http\Controllers\Admin\ReligiousProgramController;
use App\Http\Controllers\Admin\ReligiousReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// ── Public webhooks (no auth, no CSRF) ─────────────────────────────
// Meta WhatsApp Cloud API callbacks (GET = verify handshake, POST = events).
// CSRF exclusion configured in bootstrap/app.php.
Route::match(['get', 'post'], '/api/whatsapp/webhook', [WhatsAppMessageController::class, 'webhook'])
    ->name('whatsapp.webhook');

// ---------- Auth ----------
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Password reset
    Route::get('/password/reset',          [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email',         [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}',  [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset',         [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ---------- Email Verification ----------
// Available to all authenticated users (verified or not).
// To require verification on admin routes, add 'verified' middleware to the admin group.
Route::middleware('auth')->group(function () {
    Route::get('/email/verify',  [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// ---------- Admin Panel ----------
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Roles
    Route::middleware('permission:roles.view')->group(function () {
        Route::get('roles/data', [RoleController::class, 'data'])->name('roles.data');
        Route::get('roles',      [RoleController::class, 'index'])->name('roles.index');
    });
    Route::middleware('permission:roles.create')->group(function () {
        Route::get('roles/create',  [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles',        [RoleController::class, 'store'])->name('roles.store');
    });
    Route::middleware('permission:roles.update')->group(function () {
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}',      [RoleController::class, 'update'])->name('roles.update');
    });
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')->name('roles.destroy');

    // Users
    Route::middleware('permission:users.view')->group(function () {
        Route::get('users/data', [UserController::class, 'data'])->name('users.data');
        Route::get('users',      [UserController::class, 'index'])->name('users.index');
    });
    Route::middleware('permission:users.create')->group(function () {
        Route::get('users/create',  [UserController::class, 'create'])->name('users.create');
        Route::post('users',        [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware('permission:users.update')->group(function () {
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}',      [UserController::class, 'update'])->name('users.update');
    });
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')->name('users.destroy');

    // Customers — static routes must precede dynamic {customer} routes
    Route::middleware('permission:customers.view')->group(function () {
        Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');
        Route::get('customers',      [CustomerController::class, 'index'])->name('customers.index');
    });
    Route::middleware('permission:customers.create')->group(function () {
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers',       [CustomerController::class, 'store'])->name('customers.store');
    });
    Route::middleware('permission:customers.update')->group(function () {
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}',      [CustomerController::class, 'update'])->name('customers.update');
    });
    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('permission:customers.view')->name('customers.show');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')->name('customers.destroy');

    // Settings
    Route::middleware('permission:settings.view')->group(function () {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    });
    Route::put('settings', [SettingController::class, 'update'])
        ->middleware('permission:settings.update')->name('settings.update');

    // Audit Log
    Route::middleware('permission:audit.view')->group(function () {
        Route::get('audit/data', [AuditController::class, 'data'])->name('audit.data');
        Route::get('audit',      [AuditController::class, 'index'])->name('audit.index');
    });

    // ── Religious Tourism — السياحة الدينية ─────────────────────
    Route::prefix('religious')->name('religious.')->group(function () {

        // Programs (قوالب البرامج)
        Route::middleware('permission:religious_programs.view')->group(function () {
            Route::get('programs/data', [ReligiousProgramController::class, 'data'])->name('programs.data');
            Route::get('programs',      [ReligiousProgramController::class, 'index'])->name('programs.index');
        });
        Route::middleware('permission:religious_programs.create')->group(function () {
            Route::get('programs/create', [ReligiousProgramController::class, 'create'])->name('programs.create');
            Route::post('programs',       [ReligiousProgramController::class, 'store'])->name('programs.store');
        });
        Route::middleware('permission:religious_programs.update')->group(function () {
            Route::get('programs/{program}/edit', [ReligiousProgramController::class, 'edit'])->name('programs.edit');
            Route::put('programs/{program}',      [ReligiousProgramController::class, 'update'])->name('programs.update');
        });
        Route::get('programs/{program}', [ReligiousProgramController::class, 'show'])
            ->middleware('permission:religious_programs.view')->name('programs.show');
        Route::delete('programs/{program}', [ReligiousProgramController::class, 'destroy'])
            ->middleware('permission:religious_programs.delete')->name('programs.destroy');

        // Bookings (الحجوزات)
        Route::middleware('permission:religious_bookings.view')->group(function () {
            Route::get('bookings/data', [ReligiousBookingController::class, 'data'])->name('bookings.data');
            Route::get('bookings',      [ReligiousBookingController::class, 'index'])->name('bookings.index');
        });
        Route::middleware('permission:religious_bookings.create')->group(function () {
            Route::get('bookings/create', [ReligiousBookingController::class, 'create'])->name('bookings.create');
            Route::post('bookings',       [ReligiousBookingController::class, 'store'])->name('bookings.store');
        });
        Route::middleware('permission:religious_bookings.update')->group(function () {
            Route::get('bookings/{booking}/edit', [ReligiousBookingController::class, 'edit'])->name('bookings.edit');
            Route::put('bookings/{booking}',      [ReligiousBookingController::class, 'update'])->name('bookings.update');
        });
        Route::get('bookings/{booking}', [ReligiousBookingController::class, 'show'])
            ->middleware('permission:religious_bookings.view')->name('bookings.show');
        Route::delete('bookings/{booking}', [ReligiousBookingController::class, 'destroy'])
            ->middleware('permission:religious_bookings.delete')->name('bookings.destroy');

        // Workflow transitions
        Route::post('bookings/{booking}/transition', [ReligiousBookingController::class, 'transition'])
            ->middleware('permission:religious_bookings.update')->name('bookings.transition');

        // Duplicate booking
        Route::post('bookings/{booking}/duplicate', [ReligiousBookingController::class, 'duplicate'])
            ->middleware('permission:religious_bookings.create')->name('bookings.duplicate');

        // Print (PDF) — contract, manifest, receipt
        Route::middleware('permission:religious_bookings.view')->group(function () {
            Route::get('bookings/{booking}/print/contract', [ReligiousPrintController::class, 'contract'])->name('bookings.print.contract');
            Route::get('bookings/{booking}/print/manifest', [ReligiousPrintController::class, 'manifest'])->name('bookings.print.manifest');
            Route::get('bookings/{booking}/print/receipt/{payment}', [ReligiousPrintController::class, 'receipt'])->name('bookings.print.receipt');
        });

        // ── Booking sub-resources ──────────────────────────
        Route::prefix('bookings/{booking}')->name('bookings.')->group(function () {

            // Pilgrims (المعتمرون)
            Route::middleware('permission:religious_bookings.update')->group(function () {
                Route::post('pilgrims',                       [BookingPilgrimController::class, 'store'])->name('pilgrims.store');
                Route::match(['put','post'], 'pilgrims/{pilgrim}', [BookingPilgrimController::class, 'update'])->name('pilgrims.update');
                Route::delete('pilgrims/{pilgrim}',           [BookingPilgrimController::class, 'destroy'])->name('pilgrims.destroy');
            });

            // Costs (التكاليف)
            Route::middleware('permission:religious_bookings.manage_costs')->group(function () {
                Route::post('costs',           [BookingCostController::class, 'store'])->name('costs.store');
                Route::match(['put','post'], 'costs/{cost}', [BookingCostController::class, 'update'])->name('costs.update');
                Route::delete('costs/{cost}',  [BookingCostController::class, 'destroy'])->name('costs.destroy');
            });

            // Payments (المدفوعات)
            Route::middleware('permission:religious_bookings.manage_payments')->group(function () {
                Route::post('payments',              [BookingPaymentController::class, 'store'])->name('payments.store');
                Route::match(['put','post'], 'payments/{payment}', [BookingPaymentController::class, 'update'])->name('payments.update');
                Route::delete('payments/{payment}',  [BookingPaymentController::class, 'destroy'])->name('payments.destroy');
                Route::post('payments/{payment}/mark-refund-paid', [BookingPaymentController::class, 'markRefundPaid'])->name('payments.mark_refund_paid');
            });

            // Refund approval workflow (separated permission — manager-level)
            Route::middleware('permission:religious_bookings.approve_refund')->group(function () {
                Route::post('payments/{payment}/approve-refund', [BookingPaymentController::class, 'approveRefund'])->name('payments.approve_refund');
                Route::post('payments/{payment}/reject-refund',  [BookingPaymentController::class, 'rejectRefund'])->name('payments.reject_refund');
            });

            // Accommodations (السكن)
            Route::middleware('permission:religious_bookings.update')->group(function () {
                Route::post('accommodations',                       [BookingAccommodationController::class, 'store'])->name('accommodations.store');
                Route::match(['put','post'], 'accommodations/{accommodation}', [BookingAccommodationController::class, 'update'])->name('accommodations.update');
                Route::delete('accommodations/{accommodation}',     [BookingAccommodationController::class, 'destroy'])->name('accommodations.destroy');
            });

            // Transportation (النقل)
            Route::middleware('permission:religious_bookings.update')->group(function () {
                Route::post('transportation',                              [BookingTransportationController::class, 'store'])->name('transportation.store');
                Route::match(['put','post'], 'transportation/{transportation}', [BookingTransportationController::class, 'update'])->name('transportation.update');
                Route::delete('transportation/{transportation}',           [BookingTransportationController::class, 'destroy'])->name('transportation.destroy');
            });

            // Documents (الوثائق)
            Route::middleware('permission:religious_bookings.update')->group(function () {
                Route::post('documents',                       [BookingDocumentController::class, 'store'])->name('documents.store');
                Route::get('documents/{document}/download',    [BookingDocumentController::class, 'download'])->name('documents.download');
                Route::delete('documents/{document}',          [BookingDocumentController::class, 'destroy'])->name('documents.destroy');
            });

            // Integrations (صفا + بوابة العمرة)
            Route::post('sync-safa',  [ReligiousIntegrationController::class, 'syncSafa'])
                ->middleware('permission:religious_bookings.sync_safa')->name('sync_safa');
            Route::post('sync-portal', [ReligiousIntegrationController::class, 'syncPortal'])
                ->middleware('permission:religious_bookings.sync_umrah_portal')->name('sync_portal');
        });

        // Exchange Rates (أسعار الصرف)
        Route::middleware('permission:exchange_rates.view')->group(function () {
            Route::get('exchange-rates/data', [ExchangeRateController::class, 'data'])->name('exchange_rates.data');
            Route::get('exchange-rates',      [ExchangeRateController::class, 'index'])->name('exchange_rates.index');
        });
        Route::middleware('permission:exchange_rates.manage')->group(function () {
            Route::post('exchange-rates',                   [ExchangeRateController::class, 'store'])->name('exchange_rates.store');
            Route::post('exchange-rates/sync',              [ExchangeRateController::class, 'sync'])->name('exchange_rates.sync');
            Route::delete('exchange-rates/{exchange_rate}', [ExchangeRateController::class, 'destroy'])->name('exchange_rates.destroy');
        });

        // Alerts (التنبيهات الذكية)
        Route::middleware('permission:religious.alerts.view')->group(function () {
            Route::get('alerts', [ReligiousAlertController::class, 'index'])->name('alerts.index');
        });
        Route::middleware('permission:religious.alerts.acknowledge')->group(function () {
            Route::post('alerts/scan',                 [ReligiousAlertController::class, 'scan'])->name('alerts.scan');
            Route::post('alerts/{alert}/acknowledge', [ReligiousAlertController::class, 'acknowledge'])->name('alerts.acknowledge');
        });

        // Reports (التقارير)
        Route::middleware('permission:religious.reports')->group(function () {
            Route::get('reports/trips',         [ReligiousReportController::class, 'trips'])->name('reports.trips');
            Route::get('reports/trips/export',  [ReligiousReportController::class, 'tripsExport'])->name('reports.trips.export');
        });

        // Integrations Page (صفحة التكاملات)
        Route::middleware('permission:religious_bookings.view')->group(function () {
            Route::get('integrations',                  [ReligiousIntegrationsPageController::class, 'index'])->name('integrations.index');
            Route::get('integrations/logs/{log}',       [ReligiousIntegrationsPageController::class, 'logDetail'])->name('integrations.log_detail');
        });
        Route::middleware('permission:religious_bookings.sync_safa')->group(function () {
            Route::post('integrations/test',       [ReligiousIntegrationsPageController::class, 'testConnection'])->name('integrations.test');
            Route::post('integrations/bulk-sync',  [ReligiousIntegrationsPageController::class, 'bulkSync'])->name('integrations.bulk_sync');
        });
    });

    // ── Domestic Tourism — السياحة الداخلية ─────────────────────
    Route::prefix('domestic')->name('domestic.')->group(function () {

        // Programs (قوالب البرامج الداخلية)
        Route::middleware('permission:domestic_programs.view')->group(function () {
            Route::get('programs/data', [DomesticProgramController::class, 'data'])->name('programs.data');
            Route::get('programs',      [DomesticProgramController::class, 'index'])->name('programs.index');
        });
        Route::middleware('permission:domestic_programs.create')->group(function () {
            Route::get('programs/create', [DomesticProgramController::class, 'create'])->name('programs.create');
            Route::post('programs',       [DomesticProgramController::class, 'store'])->name('programs.store');
        });
        Route::middleware('permission:domestic_programs.update')->group(function () {
            Route::get('programs/{program}/edit', [DomesticProgramController::class, 'edit'])->name('programs.edit');
            Route::put('programs/{program}',      [DomesticProgramController::class, 'update'])->name('programs.update');
        });
        Route::get('programs/{program}', [DomesticProgramController::class, 'show'])
            ->middleware('permission:domestic_programs.view')->name('programs.show');
        Route::delete('programs/{program}', [DomesticProgramController::class, 'destroy'])
            ->middleware('permission:domestic_programs.delete')->name('programs.destroy');

        // Bookings (الحجوزات الداخلية)
        Route::middleware('permission:domestic_bookings.view')->group(function () {
            Route::get('bookings/data', [DomesticBookingController::class, 'data'])->name('bookings.data');
            Route::get('bookings',      [DomesticBookingController::class, 'index'])->name('bookings.index');
        });
        Route::middleware('permission:domestic_bookings.create')->group(function () {
            Route::get('bookings/create', [DomesticBookingController::class, 'create'])->name('bookings.create');
            Route::post('bookings',       [DomesticBookingController::class, 'store'])->name('bookings.store');
        });
        Route::middleware('permission:domestic_bookings.update')->group(function () {
            Route::get('bookings/{booking}/edit', [DomesticBookingController::class, 'edit'])->name('bookings.edit');
            Route::put('bookings/{booking}',      [DomesticBookingController::class, 'update'])->name('bookings.update');
        });
        Route::get('bookings/{booking}', [DomesticBookingController::class, 'show'])
            ->middleware('permission:domestic_bookings.view')->name('bookings.show');
        Route::delete('bookings/{booking}', [DomesticBookingController::class, 'destroy'])
            ->middleware('permission:domestic_bookings.delete')->name('bookings.destroy');

        // Workflow transitions (approve / start_operations / send_to_finance / close / cancel)
        Route::post('bookings/{booking}/transition', [DomesticBookingController::class, 'transition'])
            ->middleware('permission:domestic_bookings.update')->name('bookings.transition');

        // Duplicate booking
        Route::post('bookings/{booking}/duplicate', [DomesticBookingController::class, 'duplicate'])
            ->middleware('permission:domestic_bookings.create')->name('bookings.duplicate');

        // ── Booking sub-resources ──────────────────────────
        Route::prefix('bookings/{booking}')->name('bookings.')->group(function () {

            // Costs (التكاليف)
            Route::middleware('permission:domestic_bookings.manage_costs')->group(function () {
                Route::post('costs',                          [DomesticBookingCostController::class, 'store'])->name('costs.store');
                Route::match(['put','post'], 'costs/{cost}',  [DomesticBookingCostController::class, 'update'])->name('costs.update');
                Route::delete('costs/{cost}',                 [DomesticBookingCostController::class, 'destroy'])->name('costs.destroy');
            });

            // Payments (المدفوعات)
            Route::middleware('permission:domestic_bookings.manage_payments')->group(function () {
                Route::post('payments',                              [DomesticBookingPaymentController::class, 'store'])->name('payments.store');
                Route::match(['put','post'], 'payments/{payment}',   [DomesticBookingPaymentController::class, 'update'])->name('payments.update');
                Route::delete('payments/{payment}',                  [DomesticBookingPaymentController::class, 'destroy'])->name('payments.destroy');
                Route::post('payments/{payment}/mark-refund-paid',   [DomesticBookingPaymentController::class, 'markRefundPaid'])->name('payments.mark_refund_paid');
            });

            // Refund approval (separated — manager-level)
            Route::middleware('permission:domestic_bookings.approve_refund')->group(function () {
                Route::post('payments/{payment}/approve-refund', [DomesticBookingPaymentController::class, 'approveRefund'])->name('payments.approve_refund');
                Route::post('payments/{payment}/reject-refund',  [DomesticBookingPaymentController::class, 'rejectRefund'])->name('payments.reject_refund');
            });
        });

        // Reports (التقارير الداخلية)
        Route::middleware('permission:domestic.reports')->group(function () {
            Route::get('reports/pnl-by-program',         [DomesticReportController::class, 'pnlByProgram'])->name('reports.pnl_by_program');
            Route::get('reports/pnl-by-program/export',  [DomesticReportController::class, 'pnlByProgramExport'])->name('reports.pnl_by_program.export');
        });
    });

    // ── CRM — العملاء المحتملون والصفقات ───────────────────────
    Route::prefix('crm')->name('crm.')->group(function () {

        // Leads (العملاء المحتملون)
        Route::middleware('permission:leads.view')->group(function () {
            Route::get('leads/data',   [LeadController::class, 'data'])->name('leads.data');
            Route::get('leads/kanban', [LeadController::class, 'kanban'])->name('leads.kanban');
            Route::get('leads',        [LeadController::class, 'index'])->name('leads.index');
        });
        Route::middleware('permission:leads.create')->group(function () {
            Route::get('leads/create', [LeadController::class, 'create'])->name('leads.create');
            Route::post('leads',       [LeadController::class, 'store'])->name('leads.store');
        });
        Route::middleware('permission:leads.update')->group(function () {
            Route::get('leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
            Route::put('leads/{lead}',      [LeadController::class, 'update'])->name('leads.update');
            Route::post('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.update_status');
        });
        Route::get('leads/{lead}', [LeadController::class, 'show'])
            ->middleware('permission:leads.view')->name('leads.show');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])
            ->middleware('permission:leads.delete')->name('leads.destroy');

        // Convert lead to customer
        Route::post('leads/{lead}/convert', [LeadController::class, 'convertToCustomer'])
            ->middleware('permission:leads.convert')->name('leads.convert');

        // Lead activities
        Route::prefix('leads/{lead}')->name('leads.')->group(function () {
            Route::middleware('permission:leads.activities.create')->group(function () {
                Route::post('activities', [LeadActivityController::class, 'store'])->name('activities.store');
                Route::post('activities/{activity}/mark-done', [LeadActivityController::class, 'markDone'])->name('activities.mark_done');
            });
            Route::delete('activities/{activity}', [LeadActivityController::class, 'destroy'])
                ->middleware('permission:leads.activities.delete')->name('activities.destroy');
        });

        // Opportunities (الصفقات)
        Route::middleware('permission:opportunities.view')->group(function () {
            Route::get('opportunities/data',     [OpportunityController::class, 'data'])->name('opportunities.data');
            Route::get('opportunities/pipeline', [OpportunityController::class, 'pipeline'])->name('opportunities.pipeline');
            Route::get('opportunities',          [OpportunityController::class, 'index'])->name('opportunities.index');
        });
        Route::middleware('permission:opportunities.create')->group(function () {
            Route::get('opportunities/create',   [OpportunityController::class, 'create'])->name('opportunities.create');
            Route::post('opportunities',         [OpportunityController::class, 'store'])->name('opportunities.store');
        });
        Route::middleware('permission:opportunities.update')->group(function () {
            Route::get('opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])->name('opportunities.edit');
            Route::put('opportunities/{opportunity}',      [OpportunityController::class, 'update'])->name('opportunities.update');
            Route::post('opportunities/{opportunity}/stage', [OpportunityController::class, 'updateStage'])->name('opportunities.update_stage');
        });
        Route::get('opportunities/{opportunity}', [OpportunityController::class, 'show'])
            ->middleware('permission:opportunities.view')->name('opportunities.show');
        Route::delete('opportunities/{opportunity}', [OpportunityController::class, 'destroy'])
            ->middleware('permission:opportunities.delete')->name('opportunities.destroy');

        // Convert opportunity to booking
        Route::middleware('permission:opportunities.convert')->group(function () {
            Route::get('opportunities/{opportunity}/convert',  [OpportunityController::class, 'convertForm'])->name('opportunities.convert_form');
            Route::post('opportunities/{opportunity}/convert', [OpportunityController::class, 'convert'])->name('opportunities.convert');
        });

        // WhatsApp settings (manager-level)
        Route::middleware('permission:whatsapp.manage_settings')->group(function () {
            Route::get('whatsapp/settings',  [WhatsAppSettingController::class, 'edit'])->name('whatsapp.settings.edit');
            Route::put('whatsapp/settings',  [WhatsAppSettingController::class, 'update'])->name('whatsapp.settings.update');
            Route::post('whatsapp/settings/regenerate-token', [WhatsAppSettingController::class, 'regenerateVerifyToken'])->name('whatsapp.settings.regenerate_token');
        });

        // WhatsApp messages (logs + send)
        Route::middleware('permission:whatsapp.view_logs')->group(function () {
            Route::get('whatsapp/messages/data', [WhatsAppMessageController::class, 'data'])->name('whatsapp.messages.data');
            Route::get('whatsapp/messages',      [WhatsAppMessageController::class, 'index'])->name('whatsapp.messages.index');
            Route::get('whatsapp/messages/{message}', [WhatsAppMessageController::class, 'show'])->name('whatsapp.messages.show');

            // Chat (WhatsApp-style conversation UI)
            Route::get('whatsapp/chat', [WhatsAppChatController::class, 'index'])->name('whatsapp.chat.index');
            Route::get('whatsapp/chat/conversations', [WhatsAppChatController::class, 'conversations'])->name('whatsapp.chat.conversations');
            Route::get('whatsapp/chat/thread/{phone}', [WhatsAppChatController::class, 'thread'])
                ->where('phone', '[0-9]+')->name('whatsapp.chat.thread');
        });
        Route::middleware('permission:whatsapp.send')->group(function () {
            Route::post('whatsapp/messages/send', [WhatsAppMessageController::class, 'send'])->name('whatsapp.messages.send');
            Route::post('whatsapp/chat/send', [WhatsAppChatController::class, 'send'])->name('whatsapp.chat.send');
        });
    });

    // ── HR (الموارد البشرية) ────────────────────────────────────
    Route::prefix('hr')->name('hr.')->group(function () {
        // Branches (الفروع)
        Route::middleware('permission:branches.view')->group(function () {
            Route::get('branches/data', [BranchController::class, 'data'])->name('branches.data');
            Route::get('branches',      [BranchController::class, 'index'])->name('branches.index');
        });
        Route::middleware('permission:branches.create')->group(function () {
            Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
            Route::post('branches',       [BranchController::class, 'store'])->name('branches.store');
        });
        Route::middleware('permission:branches.update')->group(function () {
            Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
            Route::put('branches/{branch}',      [BranchController::class, 'update'])->name('branches.update');
            Route::post('branches/{branch}/set-main', [BranchController::class, 'setMain'])->name('branches.set_main');
        });
        Route::get('branches/{branch}', [BranchController::class, 'show'])
            ->middleware('permission:branches.view')->name('branches.show');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
            ->middleware('permission:branches.delete')->name('branches.destroy');

        // Departments (الأقسام)
        Route::middleware('permission:departments.view')->group(function () {
            Route::get('departments/data', [DepartmentController::class, 'data'])->name('departments.data');
            Route::get('departments',      [DepartmentController::class, 'index'])->name('departments.index');
        });
        Route::middleware('permission:departments.create')->group(function () {
            Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
            Route::post('departments',       [DepartmentController::class, 'store'])->name('departments.store');
        });
        Route::middleware('permission:departments.update')->group(function () {
            Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
            Route::put('departments/{department}',      [DepartmentController::class, 'update'])->name('departments.update');
        });
        Route::get('departments/{department}', [DepartmentController::class, 'show'])
            ->middleware('permission:departments.view')->name('departments.show');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])
            ->middleware('permission:departments.delete')->name('departments.destroy');

        // Positions (الوظائف)
        Route::middleware('permission:positions.view')->group(function () {
            Route::get('positions/data', [PositionController::class, 'data'])->name('positions.data');
            Route::get('positions',      [PositionController::class, 'index'])->name('positions.index');
        });
        Route::middleware('permission:positions.create')->group(function () {
            Route::get('positions/create', [PositionController::class, 'create'])->name('positions.create');
            Route::post('positions',       [PositionController::class, 'store'])->name('positions.store');
        });
        Route::middleware('permission:positions.update')->group(function () {
            Route::get('positions/{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');
            Route::put('positions/{position}',      [PositionController::class, 'update'])->name('positions.update');
        });
        Route::get('positions/{position}', [PositionController::class, 'show'])
            ->middleware('permission:positions.view')->name('positions.show');
        Route::delete('positions/{position}', [PositionController::class, 'destroy'])
            ->middleware('permission:positions.delete')->name('positions.destroy');

        // Employees (الموظفين)
        Route::middleware('permission:employees.view')->group(function () {
            Route::get('employees/data', [EmployeeController::class, 'data'])->name('employees.data');
            Route::get('employees',      [EmployeeController::class, 'index'])->name('employees.index');
        });
        Route::middleware('permission:employees.create')->group(function () {
            Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('employees',       [EmployeeController::class, 'store'])->name('employees.store');
        });
        Route::middleware('permission:employees.update')->group(function () {
            Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('employees/{employee}',      [EmployeeController::class, 'update'])->name('employees.update');
        });
        Route::get('employees/{employee}', [EmployeeController::class, 'show'])
            ->middleware('permission:employees.view')->name('employees.show');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
            ->middleware('permission:employees.delete')->name('employees.destroy');

        // ── Payroll (الرواتب) — Sprint 6 Step 5.4 ─────────────────────
        // Note: static segments (runs/data, runs/create) MUST appear before
        // runs/{run} otherwise the wildcard match wins and treats "create"
        // as a ULID, returning 404.
        Route::prefix('payroll')->name('payroll.')->group(function () {

            Route::middleware('permission:payroll.view')->group(function () {
                Route::get('runs/data', [PayrollRunController::class, 'data'])->name('runs.data');
                Route::get('runs',      [PayrollRunController::class, 'index'])->name('runs.index');
            });

            Route::middleware('permission:payroll.process')->group(function () {
                Route::get('runs/create',           [PayrollRunController::class, 'create'])->name('runs.create');
                Route::post('runs',                 [PayrollRunController::class, 'store'])->name('runs.store');
                Route::post('runs/{run}/calculate', [PayrollRunController::class, 'calculate'])->name('runs.calculate');
                Route::delete('runs/{run}',         [PayrollRunController::class, 'destroy'])->name('runs.destroy');
            });

            Route::middleware('permission:payroll.approve')->group(function () {
                Route::post('runs/{run}/approve', [PayrollRunController::class, 'approve'])->name('runs.approve');
                Route::post('runs/{run}/post',    [PayrollRunController::class, 'post'])->name('runs.post');
                Route::post('runs/{run}/cancel',  [PayrollRunController::class, 'cancel'])->name('runs.cancel');
            });

            // Show route comes LAST so static segments above are matched first.
            Route::get('runs/{run}', [PayrollRunController::class, 'show'])
                ->middleware('permission:payroll.view')->name('runs.show');
        });
    });

    // ── Service catalogs (طيران، تأشيرات، فنادق، نقل) ─────────────
    Route::prefix('catalog')->name('catalog.')->group(function () {

        // Airlines (الطيران)
        Route::middleware('permission:catalog.airlines.view')->group(function () {
            Route::get('airlines/data', [AirlineController::class, 'data'])->name('airlines.data');
            Route::get('airlines',      [AirlineController::class, 'index'])->name('airlines.index');
        });
        Route::middleware('permission:catalog.airlines.manage')->group(function () {
            Route::get('airlines/create',      [AirlineController::class, 'create'])->name('airlines.create');
            Route::post('airlines',            [AirlineController::class, 'store'])->name('airlines.store');
            Route::get('airlines/{airline}/edit', [AirlineController::class, 'edit'])->name('airlines.edit');
            Route::put('airlines/{airline}',   [AirlineController::class, 'update'])->name('airlines.update');
            Route::delete('airlines/{airline}', [AirlineController::class, 'destroy'])->name('airlines.destroy');
        });

        // Visa types (التأشيرات)
        Route::middleware('permission:catalog.visas.view')->group(function () {
            Route::get('visas/data', [VisaTypeController::class, 'data'])->name('visas.data');
            Route::get('visas',      [VisaTypeController::class, 'index'])->name('visas.index');
        });
        Route::middleware('permission:catalog.visas.manage')->group(function () {
            Route::get('visas/create',     [VisaTypeController::class, 'create'])->name('visas.create');
            Route::post('visas',           [VisaTypeController::class, 'store'])->name('visas.store');
            Route::get('visas/{visa}/edit', [VisaTypeController::class, 'edit'])->name('visas.edit');
            Route::put('visas/{visa}',     [VisaTypeController::class, 'update'])->name('visas.update');
            Route::delete('visas/{visa}',  [VisaTypeController::class, 'destroy'])->name('visas.destroy');
        });

        // Hotels (الفنادق)
        Route::middleware('permission:catalog.hotels.view')->group(function () {
            Route::get('hotels/data', [HotelController::class, 'data'])->name('hotels.data');
            Route::get('hotels',      [HotelController::class, 'index'])->name('hotels.index');
        });
        Route::middleware('permission:catalog.hotels.manage')->group(function () {
            Route::get('hotels/create',      [HotelController::class, 'create'])->name('hotels.create');
            Route::post('hotels',            [HotelController::class, 'store'])->name('hotels.store');
            Route::get('hotels/{hotel}/edit', [HotelController::class, 'edit'])->name('hotels.edit');
            Route::put('hotels/{hotel}',     [HotelController::class, 'update'])->name('hotels.update');
            Route::delete('hotels/{hotel}',  [HotelController::class, 'destroy'])->name('hotels.destroy');
        });

        // Transport providers (النقل)
        Route::middleware('permission:catalog.transport.view')->group(function () {
            Route::get('transport/data', [TransportProviderController::class, 'data'])->name('transport.data');
            Route::get('transport',      [TransportProviderController::class, 'index'])->name('transport.index');
        });
        Route::middleware('permission:catalog.transport.manage')->group(function () {
            Route::get('transport/create',           [TransportProviderController::class, 'create'])->name('transport.create');
            Route::post('transport',                 [TransportProviderController::class, 'store'])->name('transport.store');
            Route::get('transport/{transport}/edit', [TransportProviderController::class, 'edit'])->name('transport.edit');
            Route::put('transport/{transport}',      [TransportProviderController::class, 'update'])->name('transport.update');
            Route::delete('transport/{transport}',   [TransportProviderController::class, 'destroy'])->name('transport.destroy');
        });
    });

    // ── Reports hub (التقارير) ─────────────────────────────────────
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('reports', [ReportsHubController::class, 'index'])->name('reports.hub');

        // Analytics reports — 7 تقارير تحليلية موحّدة
        Route::prefix('reports/analytics')->name('reports.analytics.')->group(function () {
            Route::get('monthly-profitability', [\App\Http\Controllers\Admin\AnalyticsReportController::class, 'monthlyProfitability'])->name('monthly_profitability');
            Route::get('top-programs',          [\App\Http\Controllers\Admin\AnalyticsReportController::class, 'topPrograms'])->name('top_programs');
            Route::get('top-customers',         [\App\Http\Controllers\Admin\AnalyticsReportController::class, 'topCustomers'])->name('top_customers');
            Route::get('new-customers',         [\App\Http\Controllers\Admin\AnalyticsReportController::class, 'newCustomers'])->name('new_customers');
            Route::get('sales-performance',     [\App\Http\Controllers\Admin\AnalyticsReportController::class, 'salesPerformance'])->name('sales_performance');
            Route::get('commissions',           [\App\Http\Controllers\Admin\AnalyticsReportController::class, 'commissions'])->name('commissions');
            Route::get('outstanding-payments',  [\App\Http\Controllers\Admin\AnalyticsReportController::class, 'outstandingPayments'])->name('outstanding_payments');
        });
    });

    // ── Suppliers (الموردون) ───────────────────────────────────────
    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('suppliers/data',           [SupplierController::class, 'data'])->name('suppliers.data');
        Route::get('suppliers',                [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/{supplier}',     [SupplierController::class, 'show'])->name('suppliers.show');
    });
    Route::middleware('permission:suppliers.create')->group(function () {
        Route::get('suppliers/create/new',     [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('suppliers',               [SupplierController::class, 'store'])->name('suppliers.store');
    });
    Route::middleware('permission:suppliers.update')->group(function () {
        Route::get('suppliers/{supplier}/edit',[SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('suppliers/{supplier}',     [SupplierController::class, 'update'])->name('suppliers.update');
    });
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.delete')->name('suppliers.destroy');

    // ── Supplier Invoices (فواتير الموردين) ────────────────────────
    Route::middleware('permission:supplier_invoices.view')->group(function () {
        Route::get('supplier-invoices/data',          [SupplierInvoiceController::class, 'data'])->name('supplier_invoices.data');
        Route::get('supplier-invoices',               [SupplierInvoiceController::class, 'index'])->name('supplier_invoices.index');
        Route::get('supplier-invoices/{invoice}',     [SupplierInvoiceController::class, 'show'])->name('supplier_invoices.show');
    });
    Route::middleware('permission:supplier_invoices.create')->group(function () {
        Route::get('supplier-invoices/create/new',    [SupplierInvoiceController::class, 'create'])->name('supplier_invoices.create');
        Route::post('supplier-invoices',              [SupplierInvoiceController::class, 'store'])->name('supplier_invoices.store');
        Route::delete('supplier-invoices/{invoice}',  [SupplierInvoiceController::class, 'destroy'])->name('supplier_invoices.destroy');
    });
    Route::middleware('permission:supplier_invoices.post')->group(function () {
        Route::post('supplier-invoices/{invoice}/post',   [SupplierInvoiceController::class, 'post'])->name('supplier_invoices.post');
    });
    Route::middleware('permission:supplier_invoices.cancel')->group(function () {
        Route::post('supplier-invoices/{invoice}/cancel', [SupplierInvoiceController::class, 'cancel'])->name('supplier_invoices.cancel');
    });

    // ── Supplier Reports ─────────────────────────────────────────
    Route::middleware('permission:suppliers.reports')->group(function () {
        Route::get('supplier-statement',         [SupplierStatementController::class, 'index'])->name('suppliers.statement');
        Route::get('supplier-statement/print',   [SupplierStatementController::class, 'print'])->name('suppliers.statement.print');
        Route::get('supplier-statement/csv',     [SupplierStatementController::class, 'downloadCsv'])->name('suppliers.statement.csv');

        Route::get('suppliers-aging',            [SupplierAgingController::class, 'index'])->name('suppliers.aging');
        Route::get('suppliers-aging/print',      [SupplierAgingController::class, 'print'])->name('suppliers.aging.print');
        Route::get('suppliers-aging/csv',        [SupplierAgingController::class, 'downloadCsv'])->name('suppliers.aging.csv');
    });

    // ── Accounting (المحاسبة) ──────────────────────────────────────
    Route::prefix('accounting')->name('accounting.')->group(function () {

        // Chart of Accounts (دليل الحسابات)
        Route::middleware('permission:accounting.chart.view')->group(function () {
            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
        });
        Route::middleware('permission:accounting.chart.manage')->group(function () {
            Route::get('accounts/next-code',        [AccountController::class, 'nextCode'])->name('accounts.next-code');
            Route::get('accounts/create',           [AccountController::class, 'create'])->name('accounts.create');
            Route::post('accounts',                 [AccountController::class, 'store'])->name('accounts.store');
            Route::get('accounts/{account}/edit',   [AccountController::class, 'edit'])->name('accounts.edit');
            Route::put('accounts/{account}',        [AccountController::class, 'update'])->name('accounts.update');
            Route::delete('accounts/{account}',     [AccountController::class, 'destroy'])->name('accounts.destroy');
        });

        // Journal Entries (القيود اليومية)
        Route::middleware('permission:accounting.journal.view')->group(function () {
            Route::get('journal/data',          [JournalEntryController::class, 'data'])->name('journal.data');
            Route::get('journal',               [JournalEntryController::class, 'index'])->name('journal.index');
            Route::get('journal/{entry}',       [JournalEntryController::class, 'show'])->name('journal.show');
        });
        Route::middleware('permission:accounting.journal.create')->group(function () {
            Route::get('journal/create/new',    [JournalEntryController::class, 'create'])->name('journal.create');
            Route::post('journal',              [JournalEntryController::class, 'store'])->name('journal.store');
            Route::get('journal/{entry}/edit',  [JournalEntryController::class, 'edit'])->name('journal.edit');
            Route::put('journal/{entry}',       [JournalEntryController::class, 'update'])->name('journal.update');
        });
        Route::middleware('permission:accounting.journal.post')->group(function () {
            Route::post('journal/{entry}/post',   [JournalEntryController::class, 'post'])->name('journal.post');
            Route::post('journal/{entry}/cancel', [JournalEntryController::class, 'cancel'])->name('journal.cancel');
        });
        Route::middleware('permission:accounting.journal.delete')->group(function () {
            Route::delete('journal/{entry}', [JournalEntryController::class, 'destroy'])->name('journal.destroy');
        });

        // Cash boxes & bank accounts (الخزائن والبنوك)
        Route::middleware('permission:accounting.chart.view')->group(function () {
            Route::get('cash',                [CashAccountController::class, 'index'])->name('cash.index');
            Route::get('cash/{account}',      [CashAccountController::class, 'show'])->name('cash.show');
        });

        // Receipt Vouchers (سندات القبض)
        Route::prefix('vouchers')->name('vouchers.')->group(function () {
            Route::middleware('permission:accounting.vouchers.view')->group(function () {
                Route::get('receipts/data',                 [ReceiptVoucherController::class, 'data'])->name('receipts.data');
                Route::get('receipts',                      [ReceiptVoucherController::class, 'index'])->name('receipts.index');
                Route::get('receipts/{voucher}',            [ReceiptVoucherController::class, 'show'])->name('receipts.show');
                Route::get('receipts/{voucher}/print',      [ReceiptVoucherController::class, 'print'])->name('receipts.print');
            });
            Route::middleware('permission:accounting.vouchers.create')->group(function () {
                Route::get('receipts/create/new',           [ReceiptVoucherController::class, 'create'])->name('receipts.create');
                Route::post('receipts',                     [ReceiptVoucherController::class, 'store'])->name('receipts.store');
                Route::post('receipts/{voucher}/cancel',    [ReceiptVoucherController::class, 'cancel'])->name('receipts.cancel');
            });

            // Payment Vouchers (سندات الصرف)
            Route::middleware('permission:accounting.vouchers.view')->group(function () {
                Route::get('payments/data',                 [PaymentVoucherController::class, 'data'])->name('payments.data');
                Route::get('payments',                      [PaymentVoucherController::class, 'index'])->name('payments.index');
                Route::get('payments/{voucher}',            [PaymentVoucherController::class, 'show'])->name('payments.show');
                Route::get('payments/{voucher}/print',      [PaymentVoucherController::class, 'print'])->name('payments.print');
            });
            Route::middleware('permission:accounting.vouchers.create')->group(function () {
                Route::get('payments/create/new',           [PaymentVoucherController::class, 'create'])->name('payments.create');
                Route::post('payments',                     [PaymentVoucherController::class, 'store'])->name('payments.store');
                Route::post('payments/{voucher}/cancel',    [PaymentVoucherController::class, 'cancel'])->name('payments.cancel');
            });
        });

        // Accounting Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::middleware('permission:accounting.reports.trial_balance')->group(function () {
                Route::get('trial-balance',         [TrialBalanceController::class, 'index'])->name('trial_balance');
                Route::get('trial-balance/print',   [TrialBalanceController::class, 'print'])->name('trial_balance.print');
                Route::get('trial-balance/csv',     [TrialBalanceController::class, 'downloadCsv'])->name('trial_balance.csv');
            });

            Route::middleware('permission:accounting.reports.pnl')->group(function () {
                Route::get('pnl',           [PnlController::class, 'index'])->name('pnl');
                Route::get('pnl/print',     [PnlController::class, 'print'])->name('pnl.print');
                Route::get('pnl/csv',       [PnlController::class, 'downloadCsv'])->name('pnl.csv');
            });

            Route::middleware('permission:accounting.reports.general_ledger')->group(function () {
                Route::get('general-ledger',         [GeneralLedgerController::class, 'index'])->name('general_ledger');
                Route::get('general-ledger/print',   [GeneralLedgerController::class, 'print'])->name('general_ledger.print');
                Route::get('general-ledger/csv',     [GeneralLedgerController::class, 'downloadCsv'])->name('general_ledger.csv');
            });
        });
    });
});
