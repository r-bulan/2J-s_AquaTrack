<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add failure review, retry, and resolution columns to deliveries table
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('failure_reason')->nullable()->after('status');
            $table->text('failure_notes')->nullable()->after('failure_reason');
            $table->timestamp('failed_at')->nullable()->after('failure_notes');
            $table->foreignId('failed_by_user_id')->nullable()->after('failed_at')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('retry_count')->default(0)->after('failed_by_user_id');
            $table->string('failure_resolution')->nullable()->after('retry_count'); // pending_review, rescheduled, cancelled, resolved
            $table->timestamp('resolved_at')->nullable()->after('failure_resolution');
            $table->foreignId('resolved_by_user_id')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
        });

        // 2. Create delivery_attempts table for immutable attempt history
        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->foreignId('rider_id')->nullable()->constrained('riders')->nullOnDelete();
            $table->string('rider_name')->nullable();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status')->default('Assigned'); // Assigned, En Route, Failed, Delivered
            $table->string('failure_reason')->nullable();
            $table->text('failure_notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['delivery_id', 'attempt_number']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_attempts');

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by_user_id');
            $table->dropColumn('resolved_at');
            $table->dropColumn('failure_resolution');
            $table->dropColumn('retry_count');
            $table->dropConstrainedForeignId('failed_by_user_id');
            $table->dropColumn('failed_at');
            $table->dropColumn('failure_notes');
            $table->dropColumn('failure_reason');
        });
    }
};
