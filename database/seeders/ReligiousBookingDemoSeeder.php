<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ExchangeRate;
use App\Models\ReligiousProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo religious bookings — 1000 حجز ديني تجريبي.
 *
 * For each booking we fan out: pilgrims, accommodations (Mecca + Medina),
 * transport segments, cost line-items, payments, and a few alerts.
 *
 * Uses raw DB::table()->insert() for speed (bypasses observer hooks that
 * would otherwise fire 20k+ times). Totals are computed manually here.
 *
 * Run after: TeamUsersSeeder, CustomerDemoSeeder, ReligiousProgramSeeder,
 * ExchangeRateSeeder.
 */
class ReligiousBookingDemoSeeder extends Seeder
{
    private const TARGET_BOOKINGS = 1000;
    private const CHUNK_SIZE      = 50;

    // Hotel SAR rates here are agency *contract* rates (per room per night),
    // typically ~50-60% below public rack rates.
    private array $meccaHotels = [
        ['name' => 'فندق فيرمونت برج الساعة', 'grade' => '5_stars', 'distance' => 50,  'sar' => 650],
        ['name' => 'فندق هيلتون مكة سويتس', 'grade' => '5_stars', 'distance' => 200, 'sar' => 520],
        ['name' => 'فندق رافلز مكة',          'grade' => '5_stars', 'distance' => 100, 'sar' => 900],
        ['name' => 'فندق دار التوحيد إنتركونتيننتال', 'grade' => '5_stars', 'distance' => 80, 'sar' => 580],
        ['name' => 'فندق إيلاف مشاعل',       'grade' => '4_stars', 'distance' => 400, 'sar' => 280],
        ['name' => 'فندق رمادا الفيصلية',    'grade' => '4_stars', 'distance' => 350, 'sar' => 240],
        ['name' => 'فندق دار غفران',         'grade' => '4_stars', 'distance' => 600, 'sar' => 190],
        ['name' => 'فندق نزل الإيمان',       'grade' => 'economy', 'distance' => 1200,'sar' => 110],
        ['name' => 'فندق العزيزية بلازا',    'grade' => 'economy', 'distance' => 2500,'sar' => 80],
    ];

    private array $medinaHotels = [
        ['name' => 'فندق دار الإيمان طيبة',  'grade' => '5_stars', 'distance' => 50,  'sar' => 450],
        ['name' => 'فندق ميلينيوم المدينة',   'grade' => '5_stars', 'distance' => 120, 'sar' => 420],
        ['name' => 'فندق هيلتون المدينة',     'grade' => '5_stars', 'distance' => 100, 'sar' => 480],
        ['name' => 'فندق بدر الفلاح',          'grade' => '4_stars', 'distance' => 300, 'sar' => 240],
        ['name' => 'فندق دار التقوى',          'grade' => '4_stars', 'distance' => 250, 'sar' => 220],
        ['name' => 'فندق المنار',              'grade' => 'economy', 'distance' => 800, 'sar' => 130],
        ['name' => 'فندق نور المدينة',         'grade' => 'economy', 'distance' => 1500,'sar' => 95],
    ];

    private array $airlines = [
        'مصر للطيران', 'الخطوط السعودية', 'فلاي ناس',
        'طيران ناس', 'NileAir', 'الخطوط الجوية الكويتية',
    ];

    private array $busCompanies = ['شركة سابتكو السعودية', 'شركة الفائز للنقل', 'شركة المعهد للنقل'];

    public function run(): void
    {
        $customers = Customer::pluck('id')->all();
        if (empty($customers)) {
            $this->command->error('No customers found. Run CustomerDemoSeeder first.');
            return;
        }

        $programs = ReligiousProgram::all()->keyBy('id');
        if ($programs->isEmpty()) {
            $this->command->error('No religious programs found. Run ReligiousProgramSeeder first.');
            return;
        }

        $managers  = User::role('manager')->pluck('id')->all();
        $sellers   = User::role('booking-staff')->pluck('id')->all();
        $accounts  = User::role('accountant')->pluck('id')->all();
        if (empty($sellers))  $sellers  = User::pluck('id')->all();
        if (empty($managers)) $managers = User::pluck('id')->all();
        if (empty($accounts)) $accounts = User::pluck('id')->all();

        activity()->disableLogging();

        $existing = DB::table('religious_bookings')->count();
        if ($existing >= self::TARGET_BOOKINGS) {
            $this->command->warn('Religious bookings already seeded — skipping.');
            return;
        }

        $remaining = self::TARGET_BOOKINGS - $existing;

        $faker = \Faker\Factory::create('ar_SA');
        $faker->seed(2026);

        $sarRate = ExchangeRate::rateFor('SAR', 'EGP') ?: 14.0;

        $year = date('Y');

        // Seed counters from MAX(existing serial) rather than count() — count()
        // breaks when prior rows were partially deleted, leaving gaps that
        // collide on unique constraints (e.g. booking_payments.receipt_number).
        $umrahCount   = $this->maxSerial('religious_bookings', 'booking_number', "UM-{$year}-");
        $hajjCount    = $this->maxSerial('religious_bookings', 'booking_number', "HJ-{$year}-");
        $receiptCount = $this->maxSerial('booking_payments',   'receipt_number', "RCP-{$year}-");

        $batches = (int) ceil($remaining / self::CHUNK_SIZE);
        $this->command->info("Seeding {$remaining} religious bookings + related rows in {$batches} chunks of " . self::CHUNK_SIZE . "...");

        while ($remaining > 0) {
            $batchSize = min(self::CHUNK_SIZE, $remaining);

            DB::transaction(function () use (
                $batchSize, $faker, $customers, $programs,
                $managers, $sellers, $accounts, $sarRate, $year,
                &$umrahCount, &$hajjCount, &$receiptCount
            ) {
                $bookingRows        = [];
                $pilgrimRows        = [];
                $accomRows          = [];
                $transportRows      = [];
                $costRows           = [];
                $paymentRows        = [];
                $alertRows          = [];
                $documentRows       = [];

                for ($i = 0; $i < $batchSize; $i++) {
                    $programModel = $programs->random();
                    $type         = $programModel->type;
                    $isHajj       = $type === 'hajj';

                    if ($isHajj) {
                        $hajjCount++;
                        $bookingNumber = 'HJ-' . $year . '-' . str_pad((string) $hajjCount, 6, '0', STR_PAD_LEFT);
                    } else {
                        $umrahCount++;
                        $bookingNumber = 'UM-' . $year . '-' . str_pad((string) $umrahCount, 6, '0', STR_PAD_LEFT);
                    }

                    $bookingId    = (string) Str::ulid();
                    $customerId   = $faker->randomElement($customers);
                    $employeeId   = $faker->randomElement($sellers);
                    $managerId    = $faker->randomElement($managers);

                    $adults       = $faker->numberBetween(1, 4);
                    $children     = $faker->boolean(20) ? $faker->numberBetween(1, 2) : 0;
                    $infants      = $faker->boolean(10) ? 1 : 0;
                    $totalPax     = $adults + $children + $infants;

                    $duration     = $programModel->duration_days ?? ($isHajj ? 21 : 10);
                    $bookingDate  = $faker->dateTimeBetween('-14 months', '-1 month');
                    $tripDate     = (clone $bookingDate)->modify('+' . $faker->numberBetween(20, 120) . ' days');
                    $returnDate   = (clone $tripDate)->modify('+' . $duration . ' days');

                    $accommodationType = $faker->randomElement(['double','triple','quad','quad','quintuple']);
                    $visaType          = $faker->randomElement(['standard','standard','standard','haram','kaaba']);
                    $mealPlan          = $faker->randomElement(['hp','pp']);
                    $mutawifGrade      = $faker->randomElement(['economy','economy','land','5_stars']);

                    $pricePerPerson = (float) $programModel->base_price_per_person + $faker->numberBetween(-2000, 5000);
                    $sellingPrice   = round($pricePerPerson * $adults + $pricePerPerson * 0.6 * $children, 2);

                    $status = $this->pickStatus($tripDate, $faker);
                    $workflow = $this->pickWorkflow($status, $faker);

                    $now = Carbon::instance($bookingDate);
                    $createdAt = $bookingDate->format('Y-m-d H:i:s');

                    // ─── Build related rows first, sum costs, then build booking ───
                    [$rowsAccom, $accomCostSar, $accomCostEgp] = $this->buildAccommodations(
                        $bookingId, $accommodationType, $tripDate, $duration, $faker, $sarRate, $isHajj
                    );
                    $accomRows = array_merge($accomRows, $rowsAccom);

                    [$rowsTransport, $transportCostEgp] = $this->buildTransport(
                        $bookingId, $totalPax, $tripDate, $returnDate, $faker, $sarRate, $isHajj
                    );
                    $transportRows = array_merge($transportRows, $rowsTransport);

                    [$rowsPilgrims, $pilgrimSafaBarcodes] = $this->buildPilgrims(
                        $bookingId, $customerId, $adults, $children, $infants, $faker, $status, $tripDate
                    );
                    $pilgrimRows = array_merge($pilgrimRows, $rowsPilgrims);

                    // Per-bed cost share: a booking only pays for the beds they occupy,
                    // not the whole room (other beds are sold to separate bookings).
                    $paxPerRoom = match ($accommodationType) {
                        'single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4,
                        'quintuple' => 5, 'sextuple' => 6, default => 4,
                    };
                    $roomShare = min(1.0, max(0.25, $totalPax / $paxPerRoom));
                    $accomCostPaid = round($accomCostEgp * $roomShare, 2);

                    [$rowsCosts, $totalCostEgp] = $this->buildCosts(
                        $bookingId, $totalPax, $accomCostPaid, $transportCostEgp,
                        $isHajj, $managerId, $faker, $sarRate
                    );
                    $costRows = array_merge($costRows, $rowsCosts);

                    $netProfit = round($sellingPrice - $totalCostEgp, 2);

                    // Build payments according to status
                    [$rowsPayments, $receiptCount] = $this->buildPayments(
                        $bookingId, $sellingPrice, $status, $bookingDate, $tripDate,
                        $faker, $accounts, $receiptCount, $year
                    );
                    $paymentRows = array_merge($paymentRows, $rowsPayments);

                    // Alerts — only some bookings
                    if ($faker->boolean(15)) {
                        $alertRows = array_merge($alertRows, $this->buildAlert(
                            $bookingId, $pilgrimSafaBarcodes, $faker, $status, $tripDate
                        ));
                    }

                    // Documents — 60% of bookings get 1-3 documents
                    if ($faker->boolean(60)) {
                        $documentRows = array_merge($documentRows, $this->buildDocuments(
                            $bookingId, $pilgrimSafaBarcodes, $faker
                        ));
                    }

                    $cancelledAt    = $status === 'cancelled' ? (clone $bookingDate)->modify('+' . $faker->numberBetween(1, 10) . ' days')->format('Y-m-d H:i:s') : null;
                    $cancelReason   = $status === 'cancelled' ? $faker->randomElement([
                        'إلغاء بناءً على رغبة العميل',
                        'مشكلة في التأشيرة',
                        'ظرف صحي طارئ',
                        'تغيير موعد السفر',
                    ]) : null;

                    $bookingRows[] = [
                        'id'                       => $bookingId,
                        'booking_number'           => $bookingNumber,
                        'contract_number'          => 'CNT-' . $year . '-' . $faker->numerify('######'),
                        'receipt_number'           => null,
                        'customer_id'              => $customerId,
                        'program_id'               => $programModel->id,
                        'responsible_manager_id'   => $managerId,
                        'responsible_employee_id'  => $employeeId,
                        'type'                     => $type,
                        'booking_date'             => $bookingDate->format('Y-m-d'),
                        'trip_date'                => $tripDate->format('Y-m-d'),
                        'return_date'              => $returnDate->format('Y-m-d'),
                        'duration_days'            => $duration,
                        'adults_count'             => $adults,
                        'children_count'           => $children,
                        'infants_count'            => $infants,
                        'children_data'            => $children > 0
                            ? json_encode(array_map(fn ($n) => ['name' => 'طفل ' . $n, 'age' => $faker->numberBetween(3, 11)], range(1, $children)), JSON_UNESCAPED_UNICODE)
                            : null,
                        'visa_type'                => $visaType,
                        'accommodation_type'       => $accommodationType,
                        'meal_plan'                => $mealPlan,
                        'transport_type'           => 'flight',
                        'mutawif_grade'            => $mutawifGrade,
                        'selling_price'            => $sellingPrice,
                        'total_cost'               => $totalCostEgp,
                        'net_profit'               => $netProfit,
                        'exchange_rate_sar'        => $sarRate,
                        'status'                   => $status,
                        'workflow_stage'           => $workflow,
                        'safa_barcode'             => $faker->boolean(70) ? 'SAFA-' . $faker->numerify('##########') : null,
                        'safa_visa_group_number'   => $faker->boolean(60) ? 'GRP-' . $faker->numerify('########') : null,
                        'safa_synced_at'           => $faker->boolean(60) ? $createdAt : null,
                        'umrah_portal_ref'         => !$isHajj && $faker->boolean(50) ? 'UMR-PRT-' . $faker->numerify('########') : null,
                        'umrah_portal_synced_at'   => !$isHajj && $faker->boolean(50) ? $createdAt : null,
                        'cancellation_reason'      => $cancelReason,
                        'cancelled_at'             => $cancelledAt,
                        'cancelled_by'             => $cancelledAt ? $managerId : null,
                        'notes'                    => $faker->boolean(20) ? $faker->randomElement([
                            'العميل يفضل غرفة قريبة من الحرم',
                            'الرجاء التأكيد على نوع التأشيرة قبل السفر',
                            'مجموعة عائلية - يفضل تجاورهم في الفندق',
                            'يحتاج إلى كرسي متحرك في المسعى',
                        ]) : null,
                        'created_by'               => $employeeId,
                        'deleted_at'               => null,
                        'created_at'               => $createdAt,
                        'updated_at'               => $createdAt,
                    ];
                }

                DB::table('religious_bookings')->insert($bookingRows);
                if ($pilgrimRows)    foreach (array_chunk($pilgrimRows, 500)    as $chunk) DB::table('booking_pilgrims')->insert($chunk);
                if ($accomRows)      foreach (array_chunk($accomRows, 500)      as $chunk) DB::table('booking_accommodations')->insert($chunk);
                if ($transportRows)  foreach (array_chunk($transportRows, 500)  as $chunk) DB::table('booking_transportation')->insert($chunk);
                if ($costRows)       foreach (array_chunk($costRows, 500)       as $chunk) DB::table('booking_costs')->insert($chunk);
                if ($paymentRows)    foreach (array_chunk($paymentRows, 500)    as $chunk) DB::table('booking_payments')->insert($chunk);
                if ($alertRows)      foreach (array_chunk($alertRows, 500)      as $chunk) DB::table('religious_alerts')->insert($chunk);
                if ($documentRows)   foreach (array_chunk($documentRows, 500)   as $chunk) DB::table('booking_documents')->insert($chunk);
            });

            $remaining -= $batchSize;
            $this->command->getOutput()->write('.');
        }

        $this->command->newLine();
        $this->command->info('✔ Bookings seeded:');
        $this->command->info('   bookings: '         . DB::table('religious_bookings')->count());
        $this->command->info('   pilgrims: '         . DB::table('booking_pilgrims')->count());
        $this->command->info('   accommodations: '   . DB::table('booking_accommodations')->count());
        $this->command->info('   transportation: '   . DB::table('booking_transportation')->count());
        $this->command->info('   costs: '            . DB::table('booking_costs')->count());
        $this->command->info('   payments: '         . DB::table('booking_payments')->count());
        $this->command->info('   alerts: '           . DB::table('religious_alerts')->count());
        $this->command->info('   documents: '        . DB::table('booking_documents')->count());
    }

    // ─── helpers ───────────────────────────────────────────────────────

    private function pickStatus(\DateTime $tripDate, $faker): string
    {
        if ($tripDate < new \DateTime('-7 days')) {
            return $faker->randomElement(['completed','completed','completed','completed','cancelled']);
        }
        if ($tripDate < new \DateTime('+7 days')) {
            return $faker->randomElement(['in_progress','confirmed','completed']);
        }
        return $faker->randomElement(['pending','pending','confirmed','confirmed','confirmed','cancelled']);
    }

    private function pickWorkflow(string $status, $faker): string
    {
        return match ($status) {
            'completed'   => 'closed',
            'cancelled'   => 'closed',
            'in_progress' => 'operations',
            'confirmed'   => $faker->randomElement(['operations','finance']),
            default       => $faker->randomElement(['sales','manager_review']),
        };
    }

    private function buildAccommodations(string $bookingId, string $accomType, \DateTime $tripDate, int $duration, $faker, float $rate, bool $isHajj): array
    {
        $meccaNights = (int) round($duration * 0.6);
        $medinaNights = $duration - $meccaNights;
        if ($medinaNights < 1) { $medinaNights = 1; $meccaNights = max(1, $duration - 1); }

        $paxPerRoom = match ($accomType) {
            'single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4,
            'quintuple' => 5, 'sextuple' => 6, default => 4,
        };

        // Higher-tier accommodation => 5-star hotels
        $tier = match ($accomType) {
            'single','double' => '5_stars',
            'triple'          => $faker->randomElement(['4_stars','5_stars']),
            default           => $faker->randomElement(['economy','4_stars']),
        };
        $pickHotel = function (array $pool) use ($tier, $faker) {
            $filtered = array_values(array_filter($pool, fn ($h) => $h['grade'] === $tier));
            return $filtered ? $faker->randomElement($filtered) : $faker->randomElement($pool);
        };
        $mecca  = $pickHotel($this->meccaHotels);
        $medina = $pickHotel($this->medinaHotels);

        $rows  = [];
        $totalSar = 0; $totalEgp = 0;
        $now = $tripDate->format('Y-m-d H:i:s');

        foreach (['mecca' => [$mecca, $meccaNights, $tripDate], 'medina' => [$medina, $medinaNights, (clone $tripDate)->modify('+' . $meccaNights . ' days')]] as $city => [$hotel, $nights, $checkIn]) {
            $checkOut = (clone $checkIn)->modify('+' . $nights . ' days');
            $costSar  = round($hotel['sar'] * $nights, 2);
            $costEgp  = round($costSar * $rate, 2);
            $totalSar += $costSar; $totalEgp += $costEgp;

            $rows[] = [
                'id'                       => (string) Str::ulid(),
                'booking_id'               => $bookingId,
                'city'                     => $city,
                'hotel_name'               => $hotel['name'],
                'hotel_grade'              => $hotel['grade'],
                'hotel_distance_meters'    => (string) $hotel['distance'],
                'check_in_date'            => $checkIn->format('Y-m-d'),
                'check_out_date'           => $checkOut->format('Y-m-d'),
                'nights'                   => $nights,
                'rooms_count'              => 1,
                'room_type'                => $accomType,
                'pax_per_room'             => $paxPerRoom,
                'meal_plan'                => $faker->randomElement(['bb','hb','hp']),
                'room_price_per_night_sar' => $hotel['sar'],
                'total_cost_sar'           => $costSar,
                'exchange_rate'            => $rate,
                'total_cost_egp'           => $costEgp,
                'confirmation_number'      => 'CNF-' . $faker->numerify('######'),
                'notes'                    => null,
                'created_at'               => $now,
                'updated_at'               => $now,
            ];
        }

        return [$rows, $totalSar, $totalEgp];
    }

    private function buildTransport(string $bookingId, int $pax, \DateTime $tripDate, \DateTime $returnDate, $faker, float $rate, bool $isHajj): array
    {
        $rows = [];
        $totalEgp = 0;
        $now = $tripDate->format('Y-m-d H:i:s');

        // Outbound flight CAI → JED
        $flightCostPerPax = (float) $faker->numberBetween(5500, 9500);
        $totalOut = round($flightCostPerPax * $pax, 2);
        $totalEgp += $totalOut;
        $rows[] = [
            'id'                 => (string) Str::ulid(),
            'booking_id'         => $bookingId,
            'type'               => 'flight',
            'direction'          => 'outbound',
            'segment'            => 'cai_jed',
            'carrier_name'       => $faker->randomElement($this->airlines),
            'reference'          => strtoupper($faker->bothify('??####')),
            'departure_location' => 'القاهرة (CAI)',
            'arrival_location'   => 'جدة (JED)',
            'departure_at'       => $tripDate->format('Y-m-d') . ' ' . $faker->randomElement(['02:30:00','06:15:00','14:00:00','22:45:00']),
            'arrival_at'         => $tripDate->format('Y-m-d') . ' ' . $faker->randomElement(['05:30:00','09:15:00','17:00:00','01:45:00']),
            'currency'           => 'EGP',
            'cost_per_person'    => $flightCostPerPax,
            'pax_count'          => $pax,
            'total_cost'         => $totalOut,
            'exchange_rate'      => 1,
            'total_cost_egp'     => $totalOut,
            'notes'              => null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        // Return flight JED → CAI
        $returnCostPerPax = (float) $faker->numberBetween(5500, 9500);
        $totalIn = round($returnCostPerPax * $pax, 2);
        $totalEgp += $totalIn;
        $rows[] = [
            'id'                 => (string) Str::ulid(),
            'booking_id'         => $bookingId,
            'type'               => 'flight',
            'direction'          => 'inbound',
            'segment'            => 'jed_cai',
            'carrier_name'       => $rows[0]['carrier_name'],
            'reference'          => strtoupper($faker->bothify('??####')),
            'departure_location' => 'جدة (JED)',
            'arrival_location'   => 'القاهرة (CAI)',
            'departure_at'       => $returnDate->format('Y-m-d') . ' ' . $faker->randomElement(['03:30:00','08:30:00','15:00:00','23:00:00']),
            'arrival_at'         => $returnDate->format('Y-m-d') . ' ' . $faker->randomElement(['06:00:00','11:30:00','17:30:00','02:00:00']),
            'currency'           => 'EGP',
            'cost_per_person'    => $returnCostPerPax,
            'pax_count'          => $pax,
            'total_cost'         => $totalIn,
            'exchange_rate'      => 1,
            'total_cost_egp'     => $totalIn,
            'notes'              => null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        // Internal bus Mecca ↔ Medina
        $busSar = (float) $faker->numberBetween(80, 180);
        $busTotal = round($busSar * $pax, 2);
        $busEgp = round($busTotal * $rate, 2);
        $totalEgp += $busEgp;
        $rows[] = [
            'id'                 => (string) Str::ulid(),
            'booking_id'         => $bookingId,
            'type'               => 'bus',
            'direction'          => 'internal',
            'segment'            => 'mec_med',
            'carrier_name'       => $faker->randomElement($this->busCompanies),
            'reference'          => 'BUS-' . $faker->numerify('######'),
            'departure_location' => 'مكة المكرمة',
            'arrival_location'   => 'المدينة المنورة',
            'departure_at'       => null,
            'arrival_at'         => null,
            'currency'           => 'SAR',
            'cost_per_person'    => $busSar,
            'pax_count'          => $pax,
            'total_cost'         => $busTotal,
            'exchange_rate'      => $rate,
            'total_cost_egp'     => $busEgp,
            'notes'              => 'النقل بين المدينتين',
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        return [$rows, $totalEgp];
    }

    private function buildPilgrims(string $bookingId, string $customerId, int $adults, int $children, int $infants, $faker, string $status, \DateTime $tripDate): array
    {
        $rows = [];
        $barcodes = [];
        $now = $tripDate->format('Y-m-d H:i:s');

        $firstNamesM = ['أحمد','محمد','محمود','عبدالله','عبدالرحمن','إبراهيم','يوسف','عمر','خالد','حسن'];
        $firstNamesF = ['فاطمة','عائشة','مريم','زينب','خديجة','سارة','منى','هدى','نورا','رانيا'];
        $lastNames   = ['عبدالعزيز','الجوهري','الشريف','المصري','عبدالحميد','رمضان','حافظ','شلبي','الباز'];

        $relationships = ['self','spouse','parent','child','sibling','other'];
        $visaStatus = match ($status) {
            'completed','in_progress' => 'issued',
            'confirmed'               => $faker->randomElement(['issued','requested']),
            'cancelled'               => $faker->randomElement(['cancelled','rejected','pending']),
            default                   => $faker->randomElement(['pending','requested']),
        };

        $makePilgrim = function (string $ageGroup, int $idx, ?string $custId) use ($bookingId, $faker, $firstNamesM, $firstNamesF, $lastNames, $tripDate, $now, $visaStatus, $relationships, &$barcodes) {
            $gender = $faker->randomElement(['male','female']);
            $first  = $faker->randomElement($gender === 'male' ? $firstNamesM : $firstNamesF);
            $last   = $faker->randomElement($lastNames);
            $fullName = "{$first} " . $faker->randomElement($firstNamesM) . " {$last}";

            $birth = match ($ageGroup) {
                'infant' => $faker->dateTimeBetween('-2 years', '-3 months'),
                'child'  => $faker->dateTimeBetween('-11 years', '-3 years'),
                default  => $faker->dateTimeBetween('-70 years', '-19 years'),
            };

            $barcode = $visaStatus === 'issued'
                ? 'SAFA-' . $faker->unique()->numerify('############')
                : null;
            if ($barcode) $barcodes[] = $barcode;

            return [
                'id'                   => (string) Str::ulid(),
                'booking_id'           => $bookingId,
                'customer_id'          => $idx === 0 ? $custId : null,
                'full_name'            => $fullName,
                'full_name_en'         => null,
                'national_id'          => $ageGroup === 'adult' ? $faker->numerify('##############') : null,
                'passport_number'      => 'A' . $faker->numerify('########'),
                'passport_issue_date'  => $faker->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
                'passport_expiry_date' => $faker->dateTimeBetween('+1 year', '+6 years')->format('Y-m-d'),
                'gender'               => $gender,
                'birth_date'           => $birth->format('Y-m-d'),
                'age_group'            => $ageGroup,
                'nationality'          => 'مصري',
                'relationship_to_main' => $idx === 0 ? 'self' : $faker->randomElement($relationships),
                'room_assignment'      => $faker->boolean(50) ? (string) $faker->numberBetween(101, 999) : null,
                'bed_number'           => $faker->boolean(40) ? $faker->numberBetween(1, 4) : null,
                'safa_barcode'         => $barcode,
                'visa_number'          => $visaStatus === 'issued' ? 'VS-' . $faker->numerify('########') : null,
                'visa_status'          => $visaStatus,
                'visa_issued_date'     => $visaStatus === 'issued' ? $faker->dateTimeBetween('-90 days', '-1 day')->format('Y-m-d') : null,
                'visa_expiry_date'     => $visaStatus === 'issued' ? $faker->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d') : null,
                'passport_image'       => null,
                'photo'                => null,
                'notes'                => null,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
        };

        for ($i = 0; $i < $adults; $i++)   $rows[] = $makePilgrim('adult',  $i,                 $customerId);
        for ($i = 0; $i < $children; $i++) $rows[] = $makePilgrim('child',  $adults + $i,       null);
        for ($i = 0; $i < $infants; $i++)  $rows[] = $makePilgrim('infant', $adults + $children + $i, null);

        return [$rows, $barcodes];
    }

    private function buildCosts(string $bookingId, int $pax, float $accomEgp, float $transportEgp, bool $isHajj, ?string $managerId, $faker, float $rate): array
    {
        $rows = [];
        $now  = now()->format('Y-m-d H:i:s');

        // Visa cost in SAR
        $visaSar = $isHajj ? $faker->numberBetween(3000, 5000) : $faker->numberBetween(450, 850);
        $rows[] = $this->makeCost($bookingId, 'visa', 'تأشيرة ' . ($isHajj ? 'حج' : 'عمرة'), 'SAR', $visaSar, $pax, 'per_person', $rate, $managerId, $now);

        // Room cost (already computed via accommodations — but kept as denormalized cost line for reporting)
        $rows[] = $this->makeCost($bookingId, 'room', 'تكلفة السكن الكلي', 'EGP', $accomEgp, 1, 'total', 1, $managerId, $now);

        // Transport cost
        $rows[] = $this->makeCost($bookingId, 'flight', 'تذاكر الطيران', 'EGP', $transportEgp * 0.85, 1, 'total', 1, $managerId, $now);
        $rows[] = $this->makeCost($bookingId, 'transport', 'النقل الداخلي', 'EGP', $transportEgp * 0.15, 1, 'total', 1, $managerId, $now);

        // Mutawif / supervision
        $mutawifSar = (float) $faker->numberBetween(150, 400);
        $rows[] = $this->makeCost($bookingId, 'mutawif', 'أتعاب المطوف', 'SAR', $mutawifSar, $pax, 'per_person', $rate, $managerId, $now);

        // Supervision
        $rows[] = $this->makeCost($bookingId, 'supervision', 'إشراف ديني', 'EGP', $faker->numberBetween(500, 1500), $pax, 'per_person', 1, $managerId, $now);

        // Miscellaneous
        $rows[] = $this->makeCost($bookingId, 'miscellaneous', 'نثريات (ماء + إفطار + هدايا)', 'EGP', $faker->numberBetween(400, 1200), $pax, 'per_person', 1, $managerId, $now);

        // Tax
        $rows[] = $this->makeCost($bookingId, 'tax', 'ضرائب وزارة السياحة', 'EGP', $faker->numberBetween(300, 900), $pax, 'per_person', 1, $managerId, $now);

        // Activation
        if ($faker->boolean(70)) {
            $rows[] = $this->makeCost($bookingId, 'activation', 'تنشيط الجواز', 'EGP', $faker->numberBetween(200, 500), $pax, 'per_person', 1, $managerId, $now);
        }

        // Commission for employee
        $rows[] = $this->makeCost($bookingId, 'commission', 'عمولة الموظف', 'EGP', $faker->numberBetween(500, 2500), 1, 'total', 1, $managerId, $now);

        // Bank fees
        if ($faker->boolean(40)) {
            $rows[] = $this->makeCost($bookingId, 'bank_fee', 'رسوم تحويل بنكي', 'EGP', $faker->numberBetween(100, 350), 1, 'total', 1, $managerId, $now);
        }

        $total = array_sum(array_column($rows, 'amount_egp'));
        return [$rows, round($total, 2)];
    }

    private function makeCost(string $bookingId, string $category, string $desc, string $currency, float $amount, int $qty, string $per, float $rate, ?string $createdBy, string $now): array
    {
        $fxRate = $currency === 'EGP' ? 1 : $rate;
        $amountEgp = round($amount * $qty * $fxRate, 2);
        return [
            'id'             => (string) Str::ulid(),
            'booking_id'     => $bookingId,
            'category'       => $category,
            'description'    => $desc,
            'currency'       => $currency,
            'amount'         => $amount,
            'exchange_rate'  => $fxRate,
            'amount_egp'     => $amountEgp,
            'quantity'       => $qty,
            'per_unit'       => $per,
            'is_revenue'     => false,
            'is_locked'      => false,
            'created_by'     => $createdBy,
            'notes'          => null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
    }

    private function buildPayments(string $bookingId, float $sellingPrice, string $status, \DateTime $bookingDate, \DateTime $tripDate, $faker, array $accounts, int $receiptCount, int $year): array
    {
        $rows = [];

        $depositPct = $faker->randomFloat(2, 0.30, 0.50);
        $deposit = round($sellingPrice * $depositPct, 2);

        $receiptCount++;
        $rows[] = $this->makePayment(
            $bookingId, $deposit, 'deposit',
            $bookingDate->format('Y-m-d'), $faker, $accounts,
            'RCP-' . $year . '-' . str_pad((string) $receiptCount, 6, '0', STR_PAD_LEFT)
        );

        if (in_array($status, ['confirmed','in_progress','completed'])) {
            $installmentPct = $faker->randomFloat(2, 0.25, 0.40);
            $installment = round($sellingPrice * $installmentPct, 2);
            $instDate = (clone $bookingDate)->modify('+' . $faker->numberBetween(15, 45) . ' days');
            $receiptCount++;
            $rows[] = $this->makePayment(
                $bookingId, $installment, 'installment',
                $instDate->format('Y-m-d'), $faker, $accounts,
                'RCP-' . $year . '-' . str_pad((string) $receiptCount, 6, '0', STR_PAD_LEFT)
            );
        }

        if (in_array($status, ['in_progress','completed'])) {
            $remaining = round($sellingPrice - array_sum(array_column($rows, 'amount')), 2);
            if ($remaining > 0) {
                $finalDate = (clone $tripDate)->modify('-' . $faker->numberBetween(1, 10) . ' days');
                $receiptCount++;
                $rows[] = $this->makePayment(
                    $bookingId, $remaining, 'final',
                    $finalDate->format('Y-m-d'), $faker, $accounts,
                    'RCP-' . $year . '-' . str_pad((string) $receiptCount, 6, '0', STR_PAD_LEFT)
                );
            }
        }

        return [$rows, $receiptCount];
    }

    private function makePayment(string $bookingId, float $amount, string $type, string $date, $faker, array $accounts, string $receipt): array
    {
        $method = $faker->randomElement(['cash','bank_transfer','credit_card','instapay','cheque']);
        return [
            'id'                    => (string) Str::ulid(),
            'booking_id'            => $bookingId,
            'receipt_number'        => $receipt,
            'payment_date'          => $date,
            'payment_type'          => $type,
            'currency'              => 'EGP',
            'amount'                => $amount,
            'exchange_rate'         => 1,
            'amount_egp'            => $amount,
            'method'                => $method,
            'bank_name'             => $method === 'bank_transfer' ? $faker->randomElement(['البنك الأهلي المصري','بنك مصر','CIB','QNB الأهلي','بنك الإسكندرية']) : null,
            'transaction_reference' => $method === 'bank_transfer' ? 'TRX-' . $faker->numerify('############') : null,
            'cheque_number'         => $method === 'cheque' ? $faker->numerify('########') : null,
            'cheque_due_date'       => $method === 'cheque' ? $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d') : null,
            'received_by'           => $accounts ? $faker->randomElement($accounts) : null,
            'notes'                 => null,
            'attachment'            => null,
            'created_at'            => $date . ' 12:00:00',
            'updated_at'            => $date . ' 12:00:00',
        ];
    }

    private function buildAlert(string $bookingId, array $pilgrimBarcodes, $faker, string $status, \DateTime $tripDate): array
    {
        $now = now()->format('Y-m-d H:i:s');
        $type = $faker->randomElement(['passport_expiring','visa_overdue','payment_overdue','trip_imminent','profit_low']);
        $severity = match ($type) {
            'visa_overdue','trip_imminent' => 'critical',
            'payment_overdue','passport_expiring' => 'warning',
            default => 'info',
        };
        $titles = [
            'passport_expiring' => 'جواز سفر يقارب على الانتهاء',
            'visa_overdue'      => 'لم تصدر تأشيرة الحجز بعد',
            'payment_overdue'   => 'دفعة متأخرة عن موعدها',
            'trip_imminent'     => 'رحلة وشيكة وبيانات ناقصة',
            'profit_low'        => 'هامش ربح أقل من الحد المعتمد',
        ];

        return [[
            'id'                => (string) Str::ulid(),
            'booking_id'        => $bookingId,
            'pilgrim_id'        => null,
            'type'              => $type,
            'severity'          => $severity,
            'title'             => $titles[$type],
            'message'           => 'يُرجى مراجعة الحجز ومعالجة هذه الحالة في أقرب وقت.',
            'context'           => json_encode(['trip_date' => $tripDate->format('Y-m-d')], JSON_UNESCAPED_UNICODE),
            'is_acknowledged'   => $faker->boolean(30),
            'acknowledged_by'   => null,
            'acknowledged_at'   => null,
            'resolution_notes'  => null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]];
    }

    private function buildDocuments(string $bookingId, array $pilgrimBarcodes, $faker): array
    {
        $now = now()->format('Y-m-d H:i:s');
        $count = $faker->numberBetween(1, 3);
        $categories = ['contract','receipt','ticket','insurance','vaccination'];
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $cat = $faker->randomElement($categories);
            $rows[] = [
                'id'              => (string) Str::ulid(),
                'booking_id'      => $bookingId,
                'pilgrim_id'      => null,
                'category'        => $cat,
                'title'           => match ($cat) {
                    'contract'    => 'عقد الحجز الموقع',
                    'receipt'     => 'إيصال دفع',
                    'ticket'      => 'تذكرة طيران',
                    'insurance'   => 'وثيقة تأمين سفر',
                    'vaccination' => 'شهادة تطعيم',
                    default       => 'مستند',
                },
                'description'     => null,
                'file_path'       => 'demo/' . $cat . '_' . $faker->numerify('######') . '.pdf',
                'file_name'       => $cat . '_' . $faker->numerify('######') . '.pdf',
                'mime_type'       => 'application/pdf',
                'file_size_bytes' => $faker->numberBetween(50000, 800000),
                'issue_date'      => now()->subDays($faker->numberBetween(1, 200))->format('Y-m-d'),
                'expiry_date'     => $cat === 'insurance' ? now()->addMonths(6)->format('Y-m-d') : null,
                'uploaded_by'     => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }
        return $rows;
    }

    /**
     * Read the highest numeric serial currently stored for a prefixed code
     * column (e.g. 'RCP-2026-000884' → 884). Gap-safe: tolerates rows being
     * deleted out of the middle of the sequence.
     */
    private function maxSerial(string $table, string $column, string $prefix): int
    {
        $startPos = strlen($prefix) + 1;
        $value = DB::table($table)
            ->where($column, 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING({$column}, {$startPos}) AS UNSIGNED)) as m")
            ->value('m');
        return (int) ($value ?? 0);
    }
}
