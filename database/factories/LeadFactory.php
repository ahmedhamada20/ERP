<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            // Explicit code so we don't race the auto-generator in parallel tests
            'code'            => 'LEAD-TEST-' . fake()->unique()->numerify('######'),
            'full_name'       => fake()->name(),
            'phone'           => '+20100' . fake()->unique()->numerify('#######'),
            'whatsapp'        => null, // boot() will copy phone if empty
            'source'          => 'website',
            'status'          => 'new',
            'interest_type'   => 'umrah',
            'estimated_value' => 10000,
        ];
    }

    public function ofStatus(string $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function withInterest(string $interest): static
    {
        return $this->state(['interest_type' => $interest]);
    }
}
