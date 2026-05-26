<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'code'               => 'SUP-TEST-' . fake()->unique()->numerify('####'),
            'name'               => 'مورد ' . fake()->lastName(),
            'type'               => fake()->randomElement(['hotel', 'airline', 'transport', 'visa', 'other']),
            'phone'              => '01' . fake()->numerify('#########'),
            'email'              => fake()->unique()->safeEmail(),
            'country'            => 'مصر',
            'currency'           => 'EGP',
            'payment_terms_days' => 30,
            'is_active'          => true,
        ];
    }

    public function ofType(string $type): static
    {
        return $this->state(['type' => $type]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
