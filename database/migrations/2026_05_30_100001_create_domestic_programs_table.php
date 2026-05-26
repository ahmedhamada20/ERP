<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domestic tourism programs — قوالب البرامج السياحية الداخلية.
 *
 * Covers: hotel-only stays, full packages, day trips, cruises, desert/beach
 * camps, and event packages (weddings/conferences). Mirrors the religious
 * programs structure but drops visa/mutawif and adds destination + lodging
 * defaults relevant to local Egyptian tourism.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domestic_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود البرنامج (e.g. DOM-2026-0001)');
            $table->string('name')->comment('اسم البرنامج بالعربي');
            $table->string('name_en')->nullable();

            $table->enum('type', ['hotel_only', 'package', 'day_trip', 'cruise', 'camp', 'event'])
                  ->comment('نوع البرنامج');
            $table->string('season')->nullable()->comment('e.g. 2026-Summer, 2026-Eid');

            // Destination (مصر افتراضياً — يدعم لاحقاً السياحة العربية)
            $table->string('destination_country', 80)->default('Egypt')->comment('الدولة');
            $table->string('destination_city')->comment('المدينة (الغردقة / شرم الشيخ / الإسكندرية ...)');
            $table->string('destination_area')->nullable()->comment('المنطقة (مرسى علم / السهل الشمالي ...)');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('duration_days')->comment('مدة الرحلة بالأيام');
            $table->unsignedSmallInteger('duration_nights')->default(0)->comment('عدد الليالي');

            $table->enum('default_accommodation_grade', ['economy', '3_stars', '4_stars', '5_stars', 'resort'])
                  ->default('4_stars');
            $table->enum('default_transport_type', ['none', 'bus', 'minivan', 'private_car', 'train', 'flight'])
                  ->default('bus');
            $table->enum('default_meal_plan', ['ro', 'bb', 'hb', 'fb', 'ai'])
                  ->default('bb')->comment('RO/BB/HB/FB/All-Inclusive');

            $table->decimal('base_price_per_person', 12, 2)->default(0)->comment('سعر البيع المرجعي للفرد');
            $table->unsignedSmallInteger('min_guests')->default(1);
            $table->unsignedSmallInteger('max_guests')->default(100);

            $table->text('inclusions')->nullable()->comment('ما يشمله البرنامج');
            $table->text('exclusions')->nullable()->comment('ما لا يشمله البرنامج');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false)->comment('متاح للحجز عبر الموقع');

            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['destination_city', 'type']);
            $table->index(['season', 'type']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domestic_programs');
    }
};
