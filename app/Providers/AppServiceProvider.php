<?php

namespace App\Providers;

use App\Models\BookingPayment;
use App\Models\DomesticBooking;
use App\Models\DomesticBookingPayment;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\ReligiousBooking;
use App\Observers\BookingPaymentObserver;
use App\Observers\DomesticBookingObserver;
use App\Observers\DomesticBookingPaymentObserver;
use App\Observers\LeadObserver;
use App\Observers\OpportunityObserver;
use App\Observers\ReligiousBookingObserver;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use our themed pagination view everywhere {{ $items->links() }} is called.
        Paginator::defaultView('vendor.pagination.corex');
        Paginator::defaultSimpleView('vendor.pagination.corex');

        // Auto-post booking payments to the General Ledger (Sprint 2 Step 8).
        BookingPayment::observe(BookingPaymentObserver::class);

        // Auto-post domestic booking payments (Sprint 4 Step 5).
        DomesticBookingPayment::observe(DomesticBookingPaymentObserver::class);

        // Auto-send WhatsApp notifications on booking confirmation (Sprint 5 Step 5).
        ReligiousBooking::observe(ReligiousBookingObserver::class);
        DomesticBooking::observe(DomesticBookingObserver::class);

        // Auto-invalidate CRM KPI cache on any change to Lead/Opportunity
        // (closes the gap where API/console mutations left stale stats).
        Lead::observe(LeadObserver::class);
        Opportunity::observe(OpportunityObserver::class);

        // After any migration batch (i.e. every deploy that runs `migrate`),
        // heal the sequences table so it never falls behind the real data —
        // prevents the "Duplicate entry" booking-number errors after a restore.
        // Skipped during the test suite to keep migration setup quiet/fast.
        Event::listen(MigrationsEnded::class, function () {
            if (app()->runningUnitTests()) {
                return;
            }

            Artisan::call('sequences:sync');
        });
    }
}
