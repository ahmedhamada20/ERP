<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Sequence;

class DomesticBookingPayment extends Model
{
    use HasUlids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['booking_id', 'receipt_number', 'payment_type', 'amount_egp', 'method', 'cash_account_id', 'refund_status', 'approved_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('domestic_booking_payment');
    }

    protected $fillable = [
        'booking_id', 'receipt_number', 'payment_date', 'payment_type',
        'currency', 'amount', 'exchange_rate', 'amount_egp',
        'method', 'cash_account_id', 'bank_name', 'transaction_reference',
        'cheque_number', 'cheque_due_date',
        'received_by', 'notes', 'attachment',
        'refund_reason', 'refunded_payment_id', 'refund_status',
        'approved_by', 'approved_at', 'approval_notes',
        'journal_entry_id',
    ];

    protected $casts = [
        'payment_date'    => 'date',
        'cheque_due_date' => 'date',
        'amount'          => 'decimal:2',
        'exchange_rate'   => 'decimal:4',
        'amount_egp'      => 'decimal:2',
        'approved_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DomesticBookingPayment $payment) {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = self::generateReceiptNumber();
            }
            if (auth()->check() && empty($payment->received_by)) {
                $payment->received_by = auth()->id();
            }
            if ($payment->payment_type === 'refund' && empty($payment->refund_status)) {
                $payment->refund_status = 'pending';
            }
            if ($payment->payment_type !== 'refund') {
                $payment->refund_status      = null;
                $payment->refunded_payment_id = null;
                $payment->approved_by        = null;
                $payment->approved_at        = null;
            }
        });

        static::saving(function (DomesticBookingPayment $payment) {
            $rate = $payment->currency === 'EGP' ? 1 : (float) $payment->exchange_rate;
            $payment->amount_egp = round((float) $payment->amount * $rate, 2);
        });
    }

    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $next = Sequence::next('domestic_booking_payment:' . $year);

        return 'DRCP-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function booking()         { return $this->belongsTo(DomesticBooking::class, 'booking_id'); }
    public function receiver()        { return $this->belongsTo(User::class, 'received_by'); }
    public function approver()        { return $this->belongsTo(User::class, 'approved_by'); }
    public function refundedPayment() { return $this->belongsTo(self::class, 'refunded_payment_id'); }
    public function refundEntries()   { return $this->hasMany(self::class, 'refunded_payment_id'); }
    public function journalEntry()    { return $this->belongsTo(JournalEntry::class); }
    public function cashAccount()     { return $this->belongsTo(Account::class, 'cash_account_id'); }

    public function isRefund(): bool     { return $this->payment_type === 'refund'; }
    public function isPaidRefund(): bool { return $this->isRefund() && $this->refund_status === 'paid'; }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'cash'          => 'نقدي',
            'bank_transfer' => 'تحويل بنكي',
            'credit_card'   => 'بطاقة ائتمان',
            'cheque'        => 'شيك',
            'instapay'      => 'إنستا باي',
            'vodafone_cash' => 'فودافون كاش',
            default         => $this->method,
        };
    }

    public function getRefundStatusLabelAttribute(): ?string
    {
        return match ($this->refund_status) {
            'pending'  => 'قيد الموافقة',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            'paid'     => 'تم الصرف',
            default    => null,
        };
    }
}
