<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('barangay')->nullable();
            $table->string('area')->nullable();
            $table->decimal('jug_deposit', 10, 2)->default(0);
            $table->string('status')->default('Active'); // Active, Inactive
            $table->integer('avg_reorder_days')->default(7);
            $table->date('last_order_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
        });

        // 2. Dedicated Riders Table (Staff completely removed)
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('status')->default('Active'); // Active, Inactive
            $table->decimal('wage_rate', 10, 2)->default(0);
            $table->decimal('cash_advance', 10, 2)->default(0);
            $table->string('area')->nullable();
            $table->string('vehicle')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // 3. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('customer_name');
            $table->date('order_date');
            $table->string('status')->default('Pending'); // Pending, Confirmed, Out for Delivery, Delivered, Cancelled
            $table->string('type')->default('Walk-in'); // Walk-in, Phone, Online, Recurring
            $table->integer('jug_count')->default(1);
            $table->string('gallon_type'); // Round, Flat
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_status')->default('Unpaid'); // Paid, Unpaid, Credit
            $table->string('payment_method')->default('Cash'); // Cash, GCash, Maya, Credit
            $table->text('delivery_address')->nullable();
            $table->string('preferred_time')->nullable();
            $table->foreignId('rider_id')->nullable()->constrained('riders')->nullOnDelete();
            $table->string('rider_name')->nullable();
            $table->integer('route_order')->default(99);
            $table->boolean('recurring')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_date');
            $table->index('status');
        });

        // 4. Deliveries (One delivery per order: order_id is UNIQUE)
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('address');
            $table->string('area')->nullable();
            $table->foreignId('rider_id')->nullable()->constrained('riders')->nullOnDelete();
            $table->string('rider_name')->nullable();
            $table->integer('route_order')->default(99);
            $table->string('status')->default('Assigned'); // Assigned, En Route, Delivered, Failed
            $table->date('delivery_date')->nullable();
            $table->string('proof_photo')->nullable();
            $table->text('signature')->nullable();
            $table->text('notes')->nullable();
            $table->integer('jug_count')->default(1);
            $table->string('gallon_type')->default('Round');
            $table->timestamps();

            $table->index('status');
            $table->index('route_order');
        });

        // 5. Transactions (Income / Expense)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // income, expense
            $table->string('category');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('payment_method')->default('Cash');
            $table->timestamps();

            $table->index('type');
            $table->index('date');
            $table->index('category');
        });

        // 6. Jug / Gallon Ledger (One per customer)
        Schema::create('jug_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('customer_name');
            $table->integer('jugs_held')->default(0);
            $table->date('jugs_borrowed_date')->nullable();
            $table->string('deposit_status')->default('Unpaid'); // Paid, Unpaid, Waived
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // 7. Credit Ledger (One per customer)
        Schema::create('credit_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('customer_name');
            $table->decimal('amount_owed', 10, 2)->default(0);
            $table->date('last_payment_date')->nullable();
            $table->string('status')->default('Settled'); // Settled, Outstanding, Overdue
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // 8. Loyalty Records (One per customer)
        Schema::create('loyalty_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('customer_name');
            $table->integer('refills_count')->default(0);
            $table->integer('refills_needed')->default(10);
            $table->integer('free_jugs_earned')->default(0);
            $table->date('last_reward_date')->nullable();
            $table->timestamps();
        });

        // 9. Inventory Items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // Gallons, Caps, Seals, Filters, Chemicals, Other
            $table->integer('quantity')->default(0);
            $table->string('unit')->default('pcs');
            $table->integer('reorder_threshold')->default(10);
            $table->date('last_restocked')->nullable();
            $table->decimal('cost_per_unit', 10, 2)->default(0);
            $table->timestamps();

            $table->index('category');
        });

        // 10. Maintenance Logs
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_name');
            $table->string('equipment_type'); // RO Membrane, UV Sterilizer, Filter, Pump, Other
            $table->date('install_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date');
            $table->integer('service_interval_days')->default(90);
            $table->string('volume_processed')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('next_service_date');
        });

        // 11. Feedback & Complaints
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('type')->default('Feedback'); // Feedback, Complaint
            $table->integer('rating')->default(5); // 1-5
            $table->text('message');
            $table->string('status')->default('Open'); // Open, In Progress, Resolved
            $table->text('admin_response')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // 12. Settings (Key-Value)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });

        // 13. Activity / Audit Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('action');
            $table->index('entity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('loyalty_records');
        Schema::dropIfExists('credit_ledgers');
        Schema::dropIfExists('jug_ledgers');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('riders');
        Schema::dropIfExists('customers');
    }
};
