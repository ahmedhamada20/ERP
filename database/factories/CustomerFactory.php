<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'code'          => 'CUS-' . fake()->unique()->numerify('TEST-#####'),
            'full_name'     => fake()->name(),
            'full_name_en'  => fake()->name(),
            'phone'         => '01' . fake()->numerify('#########'),
            'mobile'        => '01' . fake()->numerify('#########'),
            'email'         => fake()->unique()->safeEmail(),
            'gender'        => 'male',
            'nationality'   => 'مصري',
            'country'       => 'مصر',
            'city'          => 'القاهرة',
            'type'          => 'individual',
            'status'        => 'active',
        ];
    }
}
