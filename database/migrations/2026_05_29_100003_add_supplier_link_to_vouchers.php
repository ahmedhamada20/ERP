<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional link from a voucher to a supplier (and optionally a specific invoice).
 *
 * Used primarily by payment vouchers ("سند صرف") when paying a supplier.
 * When supplier_id is set:
 *   - counter_account auto-resolves to the supplier's parent account
 *   - the payment appears in the supplier's subsidiary ledger
 *   - if supplier_invoice_id is also set, the payment is tied to that specific invoice
 *
 * Both are nullable — generic vouchers (paying rent to a non-tracked party,
 * receiving cash from a one-off customer) work as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignUlid('supplier_id')->nullable()->after('party_id')
                  ->constrained('suppliers')->nullOnDelete();
            $table->foreignUlid('supplier_invoice_id')->nullable()->after('supplier_id')
                  ->constrained('supplier_invoices')->nullOnDelete();

            $table->index(['supplier_id', 'date']);
            $table->index('supplier_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['supplier_id', 'date']);
            $table->dropIndex(['supplier_invoice_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['supplier_invoice_id']);
            $table->dropColumn(['supplier_id', 'supplier_invoice_id']);
        });
    }
};
