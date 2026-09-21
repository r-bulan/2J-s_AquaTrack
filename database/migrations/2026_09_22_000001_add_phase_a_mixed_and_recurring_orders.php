<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create recurring_orders table (using customer_id foreign key, no duplicate customer_name column)
        Schema::create('recurring_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->integer('round_count')->default(0);
            $table->integer('flat_count')->default(0);
            $table->string('frequency'); // Weekly, Biweekly, Monthly
            $table->string('status')->default('Active'); // Active, Paused, Cancelled
            $table->date('next_order_date');
            $table->date('last_generated_date')->nullable();
            $table->unsignedTinyInteger('target_day')->nullable(); // Target day of month (e.g., 31) for safe month-end advancing
            $table->string('payment_method')->default('Cash'); // Cash, GCash, Maya, Credit
            $table->text('delivery_address')->nullable();
            $table->string('preferred_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('next_order_date');
            $table->index(['customer_id', 'status']);
        });

        // 2. Add mixed quantity and recurring order linkage columns to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('round_count')->default(0)->after('jug_count');
            $table->integer('flat_count')->default(0)->after('round_count');
            $table->decimal('round_unit_price', 10, 2)->default(0)->after('unit_price');
            $table->decimal('flat_unit_price', 10, 2)->default(0)->after('round_unit_price');
            $table->foreignId('recurring_order_id')->nullable()->after('recurring')->constrained('recurring_orders')->nullOnDelete();

            // Database-level protection against generating the same recurring order twice for the same scheduled date
            $table->unique(['recurring_order_id', 'order_date'], 'orders_recurring_schedule_date_unique');
        });

        // 3. Add mixed quantity columns to deliveries
        Schema::table('deliveries', function (Blueprint $table) {
            $table->integer('round_count')->default(0)->after('jug_count');
            $table->integer('flat_count')->default(0)->after('round_count');
        });

        // 4. Backfill historical orders data based on legacy gallon_type
        DB::table('orders')->where('gallon_type', 'Round')->update([
            'round_count' => DB::raw('jug_count'),
            'round_unit_price' => DB::raw('unit_price'),
            'flat_count' => 0,
            'flat_unit_price' => 0,
        ]);

        DB::table('orders')->where('gallon_type', 'Flat')->update([
            'flat_count' => DB::raw('jug_count'),
            'flat_unit_price' => DB::raw('unit_price'),
            'round_count' => 0,
            'round_unit_price' => 0,
        ]);

        // 5. Backfill historical deliveries data based on legacy gallon_type
        DB::table('deliveries')->where('gallon_type', 'Round')->update([
            'round_count' => DB::raw('jug_count'),
            'flat_count' => 0,
        ]);

        DB::table('deliveries')->where('gallon_type', 'Flat')->update([
            'flat_count' => DB::raw('jug_count'),
            'round_count' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['round_count', 'flat_count']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['recurring_order_id']);
            $table->dropUnique('orders_recurring_schedule_date_unique');
            $table->dropColumn([
                'round_count',
                'flat_count',
                'round_unit_price',
                'flat_unit_price',
                'recurring_order_id',
            ]);
        });

        Schema::dropIfExists('recurring_orders');
    }
};
