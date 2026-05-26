<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo customers — 500 سجل عميل تجريبي.
 *
 * Generates realistic Egyptian-flavor customer data for testing DataTables,
 * search, FULLTEXT, exports and the religious bookings module. Idempotent:
 * skips if customers already exist beyond the admin scaffolding.
 *
 * Run after: TeamUsersSeeder (uses staff users as created_by).
 */
class CustomerDemoSeeder extends Seeder
{
    private const TARGET_COUNT = 500;
    private const CHUNK_SIZE   = 100;

    public function run(): void
    {
        if (Customer::count() >= self::TARGET_COUNT) {
            $this->command->warn('Customers already seeded — skipping.');
            return;
        }

        activity()->disableLogging();

        $faker = \Faker\Factory::create('ar_SA');
        $faker->seed(2026);

        $staffIds = User::query()->pluck('id')->all();

        $firstNamesM = ['أحمد','محمد','محمود','عبدالله','عبدالرحمن','إبراهيم','يوسف','عمر','خالد','حسن','حسين','مصطفى','علي','طارق','كريم','وليد','هشام','شريف','أيمن','رامي','سامي','مدحت','سعيد','رضا','فؤاد','جمال','نبيل','صلاح','ماجد','عماد','حاتم','ياسر','شادي','أمير','زياد'];
        $firstNamesF = ['فاطمة','عائشة','مريم','زينب','خديجة','سارة','منى','هدى','نورا','رانيا','هبة','دينا','شيماء','إيمان','نهى','أمل','سلمى','ياسمين','رحاب','دعاء','مروة','نادية','ليلى','سامية','ولاء','إسراء','هاجر','جنة','رنا','نوران'];
        $middleNames = ['أحمد','محمد','إبراهيم','عبدالعزيز','عبدالحميد','مصطفى','حسن','حسين','علي','محمود','عبدالستار','فؤاد','صلاح','كمال','عبدالله','رضا','يوسف','شعبان','رمضان','بدوي'];
        $lastNames   = ['عبدالعزيز','الجوهري','الشريف','الفقي','المصري','عبدالحميد','رمضان','الزهيري','حافظ','عطية','شلبي','الشاذلي','الباز','عبدالقادر','الديب','الحلواني','الشرقاوي','زكي','إسماعيل','الحديدي','نصار','شعبان','بدر','عبدالعال','الخولي','عبدالنبي','مرسي','رياض','أبوزيد','الخضري'];

        $governorates = [
            'القاهرة' => ['مدينة نصر','مصر الجديدة','المعادي','حلوان','شبرا','حدائق القبة','عين شمس','الزيتون','المرج','المطرية'],
            'الجيزة' => ['الدقي','المهندسين','العجوزة','فيصل','الهرم','إمبابة','الشيخ زايد','6 أكتوبر','البدرشين'],
            'الإسكندرية' => ['سيدي جابر','محرم بك','العامرية','المنتزه','سموحة','الميناء الشرقية','كرموز'],
            'الدقهلية' => ['المنصورة','ميت غمر','طلخا','بلقاس','السنبلاوين'],
            'الشرقية' => ['الزقازيق','بلبيس','العاشر من رمضان','أبو حماد','منيا القمح'],
            'البحيرة' => ['دمنهور','كفر الدوار','رشيد','إيتاي البارود'],
            'المنوفية' => ['شبين الكوم','منوف','أشمون','تلا','بركة السبع'],
            'القليوبية' => ['بنها','شبرا الخيمة','القناطر الخيرية','قليوب','طوخ'],
            'كفر الشيخ' => ['كفر الشيخ','دسوق','بلطيم','فوة'],
            'الغربية' => ['طنطا','المحلة الكبرى','كفر الزيات','زفتى','السنطة'],
            'الفيوم' => ['الفيوم','سنورس','إطسا','أبشواي'],
            'بني سويف' => ['بني سويف','الواسطى','ناصر','إهناسيا'],
            'المنيا' => ['المنيا','ملوي','مطاي','بني مزار'],
            'أسيوط' => ['أسيوط','ديروط','أبنوب','منفلوط'],
            'سوهاج' => ['سوهاج','أخميم','جرجا','طما','طهطا'],
            'قنا' => ['قنا','نجع حمادي','قوص','فرشوط'],
            'الأقصر' => ['الأقصر','الكرنك','الزينية','أرمنت'],
            'أسوان' => ['أسوان','إدفو','كوم أمبو','دراو'],
            'بورسعيد' => ['بورسعيد','بورفؤاد','الزهور','العرب'],
            'السويس' => ['السويس','الأربعين','عتاقة'],
            'الإسماعيلية' => ['الإسماعيلية','فايد','القنطرة شرق','التل الكبير'],
            'دمياط' => ['دمياط','رأس البر','فارسكور','الزرقا'],
            'البحر الأحمر' => ['الغردقة','سفاجا','القصير','مرسى علم'],
        ];
        $govNames = array_keys($governorates);

        $maritalOptions = ['متزوج','أعزب','أرمل','مطلق'];
        $types          = ['individual','individual','individual','individual','agency','group'];

        $year = date('Y');
        // Gap-safe: seed from MAX(serial) so deletions don't cause code collisions.
        $prefix = 'CUS-' . $year . '-';
        $startPos = strlen($prefix) + 1;
        $sequence = (int) (DB::table('customers')
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, {$startPos}) AS UNSIGNED)) as m")
            ->value('m') ?? 0);

        $batchesNeeded = ceil((self::TARGET_COUNT - Customer::count()) / self::CHUNK_SIZE);
        $this->command->info("Seeding " . self::TARGET_COUNT . " customers in {$batchesNeeded} chunks of " . self::CHUNK_SIZE . "...");

        $now = now();
        $remaining = self::TARGET_COUNT - Customer::count();

        while ($remaining > 0) {
            $batchSize = min(self::CHUNK_SIZE, $remaining);

            DB::transaction(function () use (
                $batchSize, $faker, $staffIds, $firstNamesM, $firstNamesF,
                $middleNames, $lastNames, $governorates, $govNames,
                $maritalOptions, $types, $year, $now, &$sequence
            ) {
                $rows = [];

                for ($i = 0; $i < $batchSize; $i++) {
                    $sequence++;
                    $gender = $faker->randomElement(['male','male','male','female','female']);
                    $first  = $faker->randomElement($gender === 'male' ? $firstNamesM : $firstNamesF);
                    $middle = $faker->randomElement($middleNames);
                    $father = $faker->randomElement($middleNames);
                    $last   = $faker->randomElement($lastNames);
                    $fullName = "{$first} {$middle} {$father} {$last}";

                    $birthYear  = $faker->numberBetween(1955, 2005);
                    $birthDate  = "{$birthYear}-" . str_pad((string)$faker->numberBetween(1,12),2,'0',STR_PAD_LEFT) . "-" . str_pad((string)$faker->numberBetween(1,28),2,'0',STR_PAD_LEFT);
                    $hasPassport = $faker->boolean(85);
                    $passportIssue  = $hasPassport ? $faker->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d') : null;
                    $passportExpiry = $hasPassport
                        ? \Carbon\Carbon::parse($passportIssue)->addYears(7)->format('Y-m-d')
                        : null;
                    if ($hasPassport && $faker->boolean(8)) {
                        $passportExpiry = $faker->dateTimeBetween('+1 month', '+5 months')->format('Y-m-d');
                    }

                    $governorate = $faker->randomElement($govNames);
                    $city        = $faker->randomElement($governorates[$governorate]);

                    $nationalIdPrefix = $birthYear >= 2000 ? '3' : '2';
                    $nationalId = $nationalIdPrefix
                        . substr($birthYear, 2, 2)
                        . str_pad((string)$faker->numberBetween(1,12), 2, '0', STR_PAD_LEFT)
                        . str_pad((string)$faker->numberBetween(1,28), 2, '0', STR_PAD_LEFT)
                        . str_pad((string)$faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT)
                        . str_pad((string)$faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT)
                        . $faker->numberBetween(0, 9);

                    $phone = '010' . $faker->numerify('########');

                    $createdAt = $faker->dateTimeBetween('-18 months', 'now')->format('Y-m-d H:i:s');

                    $rows[] = [
                        'id'                   => (string) Str::ulid(),
                        'code'                 => 'CUS-' . $year . '-' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                        'full_name'            => $fullName,
                        'full_name_en'         => null,
                        'national_id'          => $nationalId,
                        'passport_number'      => $hasPassport ? 'A' . $faker->numerify('########') : null,
                        'passport_issue_date'  => $passportIssue,
                        'passport_expiry_date' => $passportExpiry,
                        'passport_issue_place' => $hasPassport ? $governorate : null,
                        'gender'               => $gender,
                        'birth_date'           => $birthDate,
                        'nationality'          => 'مصري',
                        'religion'             => 'مسلم',
                        'marital_status'       => $faker->randomElement($maritalOptions),
                        'phone'                => $phone,
                        'mobile'               => $faker->boolean(60) ? '011' . $faker->numerify('########') : null,
                        'whatsapp'             => $faker->boolean(70) ? $phone : null,
                        'email'                => $faker->boolean(40)
                            ? \Illuminate\Support\Str::slug($first . $last, '.') . $faker->numberBetween(1, 99) . '@example.com'
                            : null,
                        'address'              => $faker->boolean(70) ? 'شارع ' . $faker->randomElement(['الجمهورية','النصر','الثورة','الملك فيصل','مصطفى كامل','صلاح سالم','جامعة الدول','الهرم','ترعة الزمر','شبرا']) . ' - ' . $faker->numberBetween(1, 250) : null,
                        'city'                 => $city,
                        'governorate'          => $governorate,
                        'country'              => 'مصر',
                        'type'                 => $faker->randomElement($types),
                        'status'               => $faker->randomElement(['active','active','active','active','active','active','active','active','inactive','blacklisted']),
                        'photo'                => null,
                        'passport_image'       => null,
                        'national_id_image'    => null,
                        'notes'                => $faker->boolean(15) ? $faker->randomElement([
                            'عميل دائم منذ 2020',
                            'يفضل التواصل عبر واتساب',
                            'سافر معنا حج 1444هـ',
                            'يحتاج كرسي متحرك',
                            'محرم لزوجته',
                            'لديه حساسية طعام',
                        ]) : null,
                        'created_by'           => $staffIds ? $faker->randomElement($staffIds) : null,
                        'deleted_at'           => null,
                        'created_at'           => $createdAt,
                        'updated_at'           => $createdAt,
                    ];
                }

                DB::table('customers')->insert($rows);
            });

            $remaining -= $batchSize;
            $this->command->getOutput()->write('.');
        }

        $this->command->newLine();
        $this->command->info('✔ Customers seeded. Total: ' . Customer::count());
    }
}
