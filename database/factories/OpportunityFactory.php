<?php

namespace Database\Factories;

use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        return [
            'code'             => 'OPP-TEST-' . fake()->unique()->numerify('######'),
            'title'            => 'Test opportunity ' . fake()->word(),
            'booking_type'     => 'religious',
            'sub_type'         => 'umrah',
            'destination'      => 'مكة',
            'pax_count'        => 2,
            'estimated_value'  => 20000,
            'probability'      => 60,
            'stage'            => 'proposal',
        ];
    }

    public function religious(string $subType = 'umrah'): static
    {
        return $this->state(['booking_type' => 'religious', 'sub_type' => $subType]);
    }

    public function domestic(string $subType = 'package'): static
    {
        return $this->state([
            'booking_type' => 'domestic',
            'sub_type'     => $subType,
            'destination'  => 'الغردقة',
        ]);
    }

    public function atStage(string $stage): static
    {
        return $this->state(['stage' => $stage]);
    }
}
