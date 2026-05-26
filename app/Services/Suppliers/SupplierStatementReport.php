<?php

namespace App\Services\Suppliers;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Voucher;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Subsidiary ledger (كشف حساب) for a single supplier.
 *
 * Merges chronologically:
 *   - opening_balance (initial setup)
 *   - posted supplier_invoices (credit → we owe more)
 *   - posted payment vouchers linked to this supplier (debit → we owe less)
 *
 * Running balance reflects "what we owe to the supplier" (credit-natured):
 *   invoice posted   → balance += amount_egp
 *   payment posted   → balance -= amount_egp
 *
 * Opening filter: anything before `from` date rolls into the opening balance.
 * Period filter:  anything in [from..to] is shown as a movement row.
 */
class SupplierStatementReport
{
    /**
     * @return array{
     *   supplier: Supplier,
     *   from: CarbonImmutable, to: CarbonImmutable,
     *   opening: float,
     *   total_invoices: float,
     *   total_payments: float,
     *   closing: float,
     *   lines: Collection,
     * }
     */
    public function build(Supplier $supplier, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $from = $from ? CarbonImmutable::instance($from)->startOfDay() : CarbonImmutable::now()->startOfMonth();
        $to   = $to   ? CarbonImmutable::instance($to)->endOfDay()     : CarbonImmutable::now()->endOfDay();

        $beforeFrom = $from->subDay()->endOfDay();
        $opening = $this->computeBalance($supplier, $beforeFrom);

        // Pull period activity
        $invoices = SupplierInvoice::query()
            ->where('supplier_id', $supplier->id)
            ->posted()
            ->whereDate('invoice_date', '>=', $from)
            ->whereDate('invoice_date', '<=', $to)
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get(['id', 'number', 'invoice_date', 'due_date', 'description',
                   'supplier_reference', 'currency', 'amount', 'tax_amount', 'amount_egp']);

        $payments = Voucher::query()
            ->where('supplier_id', $supplier->id)
            ->where('type', 'payment')
            ->posted()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'number', 'date', 'description', 'reference',
                   'currency', 'amount', 'amount_egp', 'supplier_invoice_id']);

        $rows = collect();

        foreach ($invoices as $i) {
            $rows->push((object) [
                'date'        => CarbonImmutable::instance($i->invoice_date),
                'type'        => 'invoice',
                'number'      => $i->number,
                'description' => $i->description,
                'reference'   => $i->supplier_reference,
                'invoice_id'  => $i->id,
                'debit'       => 0.0,                          // we don't owe less
                'credit'      => (float) $i->amount_egp,       // we owe more
                'link'        => route('admin.supplier_invoices.show', $i->id),
            ]);
        }

        foreach ($payments as $p) {
            $rows->push((object) [
                'date'        => CarbonImmutable::instance($p->date),
                'type'        => 'payment',
                'number'      => $p->number,
                'description' => $p->description,
                'reference'   => $p->reference,
                'invoice_id'  => $p->supplier_invoice_id,
                'debit'       => (float) $p->amount_egp,       // we owe less
                'credit'      => 0.0,
                'link'        => route('admin.accounting.vouchers.payments.show', $p->id),
            ]);
        }

        $rows = $rows
            ->sortBy([
                fn ($a, $b) => $a->date->lt($b->date) ? -1 : ($a->date->gt($b->date) ? 1 : 0),
                // tie-break: invoices before payments on same date
                fn ($a, $b) => strcmp($a->type, $b->type),
                fn ($a, $b) => strcmp($a->number, $b->number),
            ])
            ->values();

        // Walk forward to compute running balance from opening
        $running = $opening;
        $totalInvoices = 0.0;
        $totalPayments = 0.0;

        $rows->transform(function ($row) use (&$running, &$totalInvoices, &$totalPayments) {
            $running += $row->credit - $row->debit;
            $row->running_balance = round($running, 2);
            $totalInvoices += $row->credit;
            $totalPayments += $row->debit;
            return $row;
        });

        return [
            'supplier'       => $supplier,
            'from'           => $from,
            'to'             => $to,
            'opening'        => round($opening, 2),
            'closing'        => round($running, 2),
            'total_invoices' => round($totalInvoices, 2),
            'total_payments' => round($totalPayments, 2),
            'lines'          => $rows,
        ];
    }

    /**
     * Natural-side balance for the supplier up to a given moment, INCLUDING
     * opening_balance and all prior posted activity.
     */
    private function computeBalance(Supplier $supplier, DateTimeInterface $upTo): float
    {
        $upTo = CarbonImmutable::instance($upTo);
        $opening = (float) $supplier->opening_balance;

        $invSum = (float) SupplierInvoice::query()
            ->where('supplier_id', $supplier->id)
            ->posted()
            ->whereDate('invoice_date', '<=', $upTo)
            ->sum('amount_egp');

        $paySum = (float) Voucher::query()
            ->where('supplier_id', $supplier->id)
            ->where('type', 'payment')
            ->posted()
            ->whereDate('date', '<=', $upTo)
            ->sum('amount_egp');

        return $opening + $invSum - $paySum;
    }
}
