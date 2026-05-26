<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود العميل');
            $table->string('full_name')->comment('الاسم رباعي بالعربي');
            $table->string('full_name_en')->nullable()->comment('الاسم بالإنجليزي (للجوازات)');
            $table->string('national_id', 20)->unique()->nullable();
            $table->string('passport_number', 30)->nullable()->index();
            $table->date('passport_issue_date')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('passport_issue_place')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->date('birth_date')->nullable();
            $table->string('nationality')->default('مصري');
            $table->string('religion')->default('مسلم');
            $table->string('marital_status')->nullable()->comment('أعزب/متزوج/أرمل/مطلق');
            $table->string('phone', 20)->index();
            $table->string('mobile', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('governorate')->nullable();
            $table->string('country')->default('مصر');
            $table->enum('type', ['individual', 'agency', 'group'])->default('individual')->comment('فرد/وكيل/مجموعة');
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->string('photo')->nullable();
            $table->string('passport_image')->nullable();
            $table->string('national_id_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
