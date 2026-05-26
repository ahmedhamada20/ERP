<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log table — compatible with ULID-based subjects and causers.
 *
 * Replaces spatie/laravel-activitylog default migration which uses
 * bigint morph keys (incompatible with our ULID-based users/customers).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->create(config('activitylog.table_name'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->ulid('subject_id')->nullable();
                $table->string('subject_type')->nullable();
                $table->ulid('causer_id')->nullable();
                $table->string('causer_type')->nullable();
                $table->string('event')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->json('properties')->nullable();
                $table->timestamps();

                $table->index('log_name');
                $table->index(['subject_id', 'subject_type'], 'subject_idx');
                $table->index(['causer_id', 'causer_type'], 'causer_idx');
                $table->index('created_at');
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name'));
    }
};
