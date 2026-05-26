<?php

namespace Tests\Feature\Suppliers;

use App\Models\Account;
use App\Models\Supplier;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\SupplierDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class SupplierModelTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_code_auto_generated_on_create(): void
    {
        $s = Supplier::create([
            'name' => 'مورد بدون كود', 'type' => 'hotel', 'currency' => 'EGP',
        ]);

        $year = date('Y');
        $this->assertStringStartsWith("SUP-{$year}-", $s->code);
    }

    public function test_codes_are_sequential(): void
    {
        $s1 = Supplier::create(['name' => 'أول', 'type' => 'hotel', 'currency' => 'EGP']);
        $s2 = Supplier::create(['name' => 'تاني', 'type' => 'airline', 'currency' => 'EGP']);

        // Both codes should match SUP-YYYY-XXXX and s2 > s1
        $this->assertGreaterThan($s1->code, $s2->code);
    }

    public function test_parent_account_code_mapped_by_type(): void
    {
        $hotel     = Supplier::factory()->ofType('hotel')->make();
        $airline   = Supplier::factory()->ofType('airline')->make();
        $transport = Supplier::factory()->ofType('transport')->make();
        $visa      = Supplier::factory()->ofType('visa')->make();
        $other     = Supplier::factory()->ofType('other')->make();

        $this->assertSame('2111', $hotel->parentAccountCode());
        $this->assertSame('2112', $airline->parentAccountCode());
        $this->assertSame('2113', $transport->parentAccountCode());
        $this->assertSame('2114', $visa->parentAccountCode());
        $this->assertSame('2115', $other->parentAccountCode());
    }

    public function test_parent_account_model_resolves_to_actual_account(): void
    {
        $s = Supplier::factory()->ofType('hotel')->create();

        $parent = $s->parentAccountModel();
        $this->assertInstanceOf(Account::class, $parent);
        $this->assertSame('2111', $parent->code);
        $this->assertSame('موردين فنادق', $parent->name);
    }

    public function test_type_label_returns_arabic(): void
    {
        $hotel   = Supplier::factory()->ofType('hotel')->make();
        $airline = Supplier::factory()->ofType('airline')->make();

        $this->assertSame('فنادق', $hotel->type_label);
        $this->assertSame('طيران', $airline->type_label);
    }

    public function test_scope_active_filters_inactive(): void
    {
        Supplier::factory()->count(3)->create();
        Supplier::factory()->inactive()->count(2)->create();

        $this->assertSame(3, Supplier::active()->count());
    }

    public function test_demo_seeder_creates_5_suppliers(): void
    {
        $this->seed(SupplierDemoSeeder::class);

        $this->assertSame(5, Supplier::count());
        $this->assertNotNull(Supplier::where('type', 'hotel')->first());
        $this->assertNotNull(Supplier::where('type', 'airline')->first());
        $this->assertNotNull(Supplier::where('type', 'transport')->first());
        $this->assertNotNull(Supplier::where('type', 'visa')->first());
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $this->seed(SupplierDemoSeeder::class);
        $count = Supplier::count();
        $this->seed(SupplierDemoSeeder::class);
        $this->assertSame($count, Supplier::count());
    }
}
