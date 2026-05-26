<?php

namespace Database\Seeders;

use App\Models\ReligiousProgram;
use Illuminate\Database\Seeder;

/**
 * Demo religious programs — sample templates for sales reps to start from.
 * Safe to run on fresh installs; updateOrCreate keys make it idempotent.
 */
class ReligiousProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'code'                        => 'UMR-2026-0001',
                'name'                        => 'عمرة رمضان الاقتصادية 2026',
                'name_en'                     => 'Ramadan Umrah Economy 2026',
                'type'                        => 'umrah',
                'season'                      => '2026-Ramadan',
                'duration_days'               => 10,
                'default_visa_type'           => 'standard',
                'default_accommodation_grade' => 'economy',
                'default_transport_type'      => 'flight',
                'default_meal_plan'           => 'hp',
                'default_mutawif_grade'       => 'economy',
                'base_price_per_person'       => 35000,
                'min_pilgrims'                => 1,
                'max_pilgrims'                => 50,
                'inclusions'                  => "الطيران ذهاب وعودة\nالسكن في مكة والمدينة\nالنقل بين المدن\nالإفطار والعشاء\nالإشراف الديني",
                'exclusions'                  => "الوجبات الإضافية\nالإكراميات\nالمصاريف الشخصية",
                'is_active'                   => true,
                'is_published'                => true,
            ],
            [
                'code'                        => 'UMR-2026-0002',
                'name'                        => 'عمرة رمضان VIP 2026',
                'name_en'                     => 'Ramadan Umrah VIP 2026',
                'type'                        => 'umrah',
                'season'                      => '2026-Ramadan',
                'duration_days'               => 14,
                'default_visa_type'           => 'haram',
                'default_accommodation_grade' => '5_stars',
                'default_transport_type'      => 'flight',
                'default_meal_plan'           => 'pp',
                'default_mutawif_grade'       => '5_stars',
                'base_price_per_person'       => 85000,
                'min_pilgrims'                => 1,
                'max_pilgrims'                => 30,
                'inclusions'                  => "الطيران درجة رجال الأعمال\nسكن 5 نجوم مواجه للحرم\nنقل خاص VIP\nإقامة كاملة\nإشراف ديني متميز",
                'is_active'                   => true,
                'is_published'                => true,
            ],
            [
                'code'                        => 'HAJ-2026-0001',
                'name'                        => 'حج 2026 - برنامج اقتصادي',
                'name_en'                     => 'Hajj 2026 - Economy',
                'type'                        => 'hajj',
                'season'                      => '2026-Hajj',
                'duration_days'               => 21,
                'default_visa_type'           => 'standard',
                'default_accommodation_grade' => 'economy',
                'default_transport_type'      => 'flight',
                'default_meal_plan'           => 'hp',
                'default_mutawif_grade'       => 'economy',
                'base_price_per_person'       => 180000,
                'min_pilgrims'                => 1,
                'max_pilgrims'                => 100,
                'inclusions'                  => "الطيران ذهاب وعودة\nسكن اقتصادي في مكة والمدينة\nمخيمات منى وعرفات ومزدلفة\nالإشراف الديني\nرسوم الحج الرسمية",
                'is_active'                   => true,
                'is_published'                => true,
            ],
            [
                'code'                        => 'HAJ-2026-0002',
                'name'                        => 'حج 2026 - برنامج 5 نجوم',
                'name_en'                     => 'Hajj 2026 - 5 Stars',
                'type'                        => 'hajj',
                'season'                      => '2026-Hajj',
                'duration_days'               => 25,
                'default_visa_type'           => 'kaaba',
                'default_accommodation_grade' => '5_stars',
                'default_transport_type'      => 'flight',
                'default_meal_plan'           => 'pp',
                'default_mutawif_grade'       => '5_stars',
                'base_price_per_person'       => 350000,
                'min_pilgrims'                => 1,
                'max_pilgrims'                => 40,
                'inclusions'                  => "طيران درجة أولى\nسكن 5 نجوم في برج الساعة\nخيام VIP بتكييف خاص\nإقامة كاملة فاخرة\nمطوف مميز",
                'is_active'                   => true,
                'is_published'                => false,
            ],
        ];

        foreach ($programs as $row) {
            ReligiousProgram::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
