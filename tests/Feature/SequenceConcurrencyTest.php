<?php

namespace Tests\Feature;

use App\Models\BookingPayment;
use App\Models\DomesticBooking;
use App\Models\DomesticBookingPayment;
use App\Models\JournalEntry;
use App\Models\ReligiousBooking;
use App\Models\Sequence;
use App\Models\SupplierInvoice;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * يتحقق من أن جميع دوال generateNumber الآن تستخدم Sequence وتنتج أرقاماً
 * فريدة بدون فجوات أو تكرار، حتى عند توليد أعداد كبيرة على التوالي.
 *
 * (اختبار التزامن الحقيقي عبر threads مستحيل في PHPUnit؛ نختبر بدلاً
 * من ذلك أن الـ counter يتقدم ذرّياً وأن لا يحدث أي تكرار بعد 100 توليد.)
 */
class SequenceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_next_returns_monotonically_increasing_numbers(): void
    {
        $numbers = [];
        for ($i = 0; $i < 50; $i++) {
            $numbers[] = Sequence::next('test:basic');
        }
        $this->assertSame(range(1, 50), $numbers);
    }

    public function test_sequence_next_is_isolated_per_key(): void
    {
        Sequence::next('test:a');
        Sequence::next('test:a');
        Sequence::next('test:b');

        $this->assertSame(3, Sequence::next('test:a'));
        $this->assertSame(2, Sequence::next('test:b'));
    }

    public function test_booking_payment_receipt_numbers_are_unique_and_sequential(): void
    {
        $year = date('Y');
        $expected = [];

        for ($i = 1; $i <= 10; $i++) {
            $expected[] = 'RCP-' . $year . '-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT);
        }

        $generated = [];
        for ($i = 0; $i < 10; $i++) {
            $generated[] = BookingPayment::generateReceiptNumber();
        }

        $this->assertSame($expected, $generated);
        $this->assertCount(10, array_unique($generated), 'يجب أن تكون كل الأرقام فريدة');
    }

    public function test_domestic_booking_payment_receipt_numbers_are_unique(): void
    {
        $generated = [];
        for ($i = 0; $i < 20; $i++) {
            $generated[] = DomesticBookingPayment::generateReceiptNumber();
        }
        $this->assertCount(20, array_unique($generated));
        $this->assertStringStartsWith('DRCP-', $generated[0]);
    }

    public function test_journal_entry_numbers_are_unique(): void
    {
        $generated = [];
        for ($i = 0; $i < 20; $i++) {
            $generated[] = JournalEntry::generateNumber();
        }
        $this->assertCount(20, array_unique($generated));
        $this->assertStringStartsWith('JE-', $generated[0]);
    }

    public function test_voucher_receipt_and_payment_numbers_are_independent(): void
    {
        $vr = [];
        $vp = [];
        for ($i = 0; $i < 5; $i++) {
            $vr[] = Voucher::generateNumber('receipt');
            $vp[] = Voucher::generateNumber('payment');
        }
        // كلا السلسلتين تبدأ من 1 (لأنهما مفتاحان مختلفان)
        $this->assertStringEndsWith('-000001', $vr[0]);
        $this->assertStringEndsWith('-000001', $vp[0]);
        $this->assertStringEndsWith('-000005', $vr[4]);
        $this->assertStringEndsWith('-000005', $vp[4]);
        $this->assertStringStartsWith('VR-', $vr[0]);
        $this->assertStringStartsWith('VP-', $vp[0]);
    }

    public function test_supplier_invoice_numbers_are_unique(): void
    {
        $generated = [];
        for ($i = 0; $i < 10; $i++) {
            $generated[] = SupplierInvoice::generateNumber();
        }
        $this->assertCount(10, array_unique($generated));
        $this->assertStringStartsWith('SI-', $generated[0]);
    }

    public function test_religious_booking_hajj_and_umrah_numbers_are_independent(): void
    {
        $hj = [];
        $um = [];
        for ($i = 0; $i < 5; $i++) {
            $hj[] = ReligiousBooking::generateBookingNumber('hajj');
            $um[] = ReligiousBooking::generateBookingNumber('umrah');
        }
        $this->assertStringStartsWith('HJ-', $hj[0]);
        $this->assertStringStartsWith('UM-', $um[0]);
        $this->assertStringEndsWith('-000005', $hj[4]);
        $this->assertStringEndsWith('-000005', $um[4]);
    }

    public function test_domestic_booking_numbers_are_unique(): void
    {
        $generated = [];
        for ($i = 0; $i < 10; $i++) {
            $generated[] = DomesticBooking::generateBookingNumber();
        }
        $this->assertCount(10, array_unique($generated));
        $this->assertStringStartsWith('DM-', $generated[0]);
    }

    /**
     * يحاكي race condition بسيط: توليد دفعتين متتاليتين بسرعة
     * (لا يضمن تزامن حقيقي لكنه يثبت أن الـ insert+lock يعمل).
     */
    public function test_no_duplicate_receipt_numbers_on_rapid_generation(): void
    {
        $generated = [];
        for ($i = 0; $i < 100; $i++) {
            $generated[] = BookingPayment::generateReceiptNumber();
        }
        $this->assertSame(100, count(array_unique($generated)), 'لا يجوز تكرار أي رقم إيصال');
    }
}
