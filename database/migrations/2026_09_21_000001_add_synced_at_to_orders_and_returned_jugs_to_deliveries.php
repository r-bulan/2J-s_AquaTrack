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
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable()->after('route_order');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->integer('returned_jugs')->default(0)->after('jug_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('returned_jugs');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('synced_at');
        });
    }
};
