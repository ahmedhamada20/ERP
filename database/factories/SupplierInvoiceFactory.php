<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierInvoice>
 */
class SupplierInvoiceFactory extends Factory
{
    protected $model = SupplierInvoice::class;

    public function definition(): array
    {
        return [
            'supplier_id'        => Supplier::factory(),
            'expense_account_id' => fn () => Account::where('code', '511')->first()?->id
                                            ?? Account::factory()->create([
                                                'code' => '511', 'name' => 'تكلفة الفنادق',
                                                'type' => 'expense', 'is_group' => false,
                                            ])->id,
            'invoice_date'       => now()->toDateString(),
            'due_date'           => now()->addDays(30)->toDateString(),
            'description'        => 'فاتورة اختبار',
            'currency'           => 'EGP',
            'amount'             => 1000,
            'tax_amount'         => 0,
            'exchange_rate'      => 1,
            'status'             => 'draft',
        ];
    }

    public function withTax(float $tax): static
    {
        return $this->state(['tax_amount' => $tax]);
    }

    public function inForeign(string $currency, float $rate): static
    {
        return $this->state(['currency' => $currency, 'exchange_rate' => $rate]);
    }
}
