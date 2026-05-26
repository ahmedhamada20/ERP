<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Religious tourism programs — قوالب البرامج (حج / عمرة).
 *
 * Sales team picks a program when creating a booking; manager-level
 * permissions are required to create / edit the template itself
 * (see RolePermissionSeeder: religious_programs.*).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('religious_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود البرنامج (e.g. UMR-2026-01)');
            $table->string('name')->comment('اسم البرنامج بالعربي');
            $table->string('name_en')->nullable();
            $table->enum('type', ['hajj', 'umrah'])->comment('حج / عمرة');
            $table->string('season')->nullable()->comment('e.g. 2026-Ramadan, 2026-Hajj');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('duration_days')->comment('مدة الرحلة بالأيام');

            $table->enum('default_visa_type', ['standard', 'haram', 'kaaba'])->default('standard');
            $table->enum('default_accommodation_grade', ['economy', '4_stars', '5_stars'])->default('economy');
            $table->enum('default_transport_type', ['bus', 'train', 'vip', 'flight'])->default('flight');
            $table->enum('default_meal_plan', ['pp', 'hp'])->default('hp')->comment('P.P / H.P');
            $table->enum('default_mutawif_grade', ['economy', 'land', '5_stars'])->default('economy');

            $table->decimal('base_price_per_person', 12, 2)->default(0)->comment('سعر البيع المرجعي للفرد');
            $table->unsignedSmallInteger('min_pilgrims')->default(1);
            $table->unsignedSmallInteger('max_pilgrims')->default(100);

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
            $table->index(['season', 'type']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('religious_programs');
    }
};
