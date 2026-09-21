<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Feedback;
use App\Models\InventoryItem;
use App\Models\MaintenanceLog;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DatabaseSeeder (Category A: Essential Development & Demo Dataset)
 *
 * Produces a small, clean, deterministic, and predictable dataset:
 * - 1 Admin / Owner account
 * - 2 Rider accounts (active route delivery)
 * - 1 Primary Customer login (+ 1 secondary customer user for multi-user authorization testing)
 * - 2 Additional realistic customer records (with ledger, credit, loyalty)
 * - 4 Demonstrative orders & deliveries covering all lifecycle states (Pending, Assigned, En Route, Delivered)
 * - Minimal financial transactions (2 income, 3 operational expenses)
 * - Essential inventory items, maintenance logs, feedback, and system activity logs
 *
 * Extended multi-month historical financial data is moved to DemoDataSeeder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. System Settings & Default Pricing
        Setting::set('round_gallon_price', '35.00');
        Setting::set('flat_gallon_price', '40.00');
        Setting::set('loyalty_refills_needed', '10');

        // 2. Admin / Owner Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@twojs.test'],
            [
                'name' => 'Juan Dela Cruz (Owner)',
                'password' => Hash::make('Password123!'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 3. Riders (Users + Profiles)
        $riderUser1 = User::firstOrCreate(
            ['email' => 'rider@twojs.test'],
            [
                'name' => 'Kuya Mark Santos',
                'password' => Hash::make('Password123!'),
                'role' => 'rider',
                'email_verified_at' => now(),
            ]
        );

        $rider1 = Rider::firstOrCreate(
            ['user_id' => $riderUser1->id],
            [
                'name' => 'Kuya Mark Santos',
                'phone' => '0918-123-4567',
                'status' => 'Active',
                'wage_rate' => 450.00,
                'cash_advance' => 0.00,
                'area' => 'Zone 1 & Zone 2',
                'vehicle' => 'Honda Wave 110 w/ Sidecar (Plate 123-ABC)',
            ]
        );

        $riderUser2 = User::firstOrCreate(
            ['email' => 'arnel.rider@twojs.test'],
            [
                'name' => 'Kuya Arnel Reyes',
                'password' => Hash::make('Password123!'),
                'role' => 'rider',
                'email_verified_at' => now(),
            ]
        );

        $rider2 = Rider::firstOrCreate(
            ['user_id' => $riderUser2->id],
            [
                'name' => 'Kuya Arnel Reyes',
                'phone' => '0920-987-6543',
                'status' => 'Active',
                'wage_rate' => 450.00,
                'cash_advance' => 0.00,
                'area' => 'Zone 3 & Poblacion',
                'vehicle' => 'Rusi 125 Cargo Carrier (Plate 456-XYZ)',
            ]
        );

        // 4. Primary Customer (with User Login)
        $customerUser1 = User::firstOrCreate(
            ['email' => 'customer@twojs.test'],
            [
                'name' => 'Maria Clara',
                'password' => Hash::make('Password123!'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ]
        );

        $customer1 = Customer::firstOrCreate(
            ['user_id' => $customerUser1->id],
            [
                'name' => 'Maria Clara',
                'phone' => '0917-555-1234',
                'email' => 'customer@twojs.test',
                'address' => 'Blk 12 Lot 4 Sampaguita St., Brgy. San Antonio',
                'barangay' => 'Brgy. San Antonio',
                'area' => 'Zone 1',
                'jug_deposit' => 200.00,
                'status' => 'Active',
                'avg_reorder_days' => 5,
                'last_order_date' => now()->subDays(5)->toDateString(),
                'notes' => 'Prefers morning delivery. Ring the gate bell twice.',
            ]
        );

        if (!$customer1->jugLedger) {
            $customer1->jugLedger()->create([
                'customer_name' => $customer1->name,
                'jugs_held' => 3,
                'jugs_borrowed_date' => now()->subDays(5)->toDateString(),
                'deposit_status' => 'Paid',
                'deposit_amount' => 200.00,
            ]);
        }

        if (!$customer1->creditLedger) {
            $customer1->creditLedger()->create([
                'customer_name' => $customer1->name,
                'amount_owed' => 0.00,
                'status' => 'Settled',
            ]);
        }

        if (!$customer1->loyaltyRecord) {
            $customer1->loyaltyRecord()->create([
                'customer_name' => $customer1->name,
                'refills_count' => 7,
                'refills_needed' => 10,
                'free_jugs_earned' => 1,
                'last_reward_date' => now()->subMonths(1)->toDateString(),
            ]);
        }

        // 5. Secondary Customer (with User Login - enables multi-user customer authorization testing)
        $customerUser2 = User::firstOrCreate(
            ['email' => 'customer2@twojs.test'],
            [
                'name' => 'Juanito Pelaez',
                'password' => Hash::make('Password123!'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ]
        );

        $customer2 = Customer::firstOrCreate(
            ['user_id' => $customerUser2->id],
            [
                'name' => 'Juanito Pelaez',
                'phone' => '0917-222-3344',
                'email' => 'customer2@twojs.test',
                'address' => '45 Mabini St., Poblacion',
                'barangay' => 'Poblacion',
                'area' => 'Zone 2',
                'jug_deposit' => 200.00,
                'status' => 'Active',
                'avg_reorder_days' => 6,
                'last_order_date' => now()->subDays(2)->toDateString(),
            ]
        );

        if (!$customer2->jugLedger) {
            $customer2->jugLedger()->create([
                'customer_name' => $customer2->name,
                'jugs_held' => 2,
                'jugs_borrowed_date' => now()->subDays(2)->toDateString(),
                'deposit_status' => 'Paid',
                'deposit_amount' => 200.00,
            ]);
        }

        if (!$customer2->creditLedger) {
            $customer2->creditLedger()->create([
                'customer_name' => $customer2->name,
                'amount_owed' => 0.00,
                'status' => 'Settled',
            ]);
        }

        if (!$customer2->loyaltyRecord) {
            $customer2->loyaltyRecord()->create([
                'customer_name' => $customer2->name,
                'refills_count' => 3,
                'refills_needed' => 10,
                'free_jugs_earned' => 0,
            ]);
        }

        // 6. Additional Essential Customers (Store & Business Profiles)
        $additionalCustomers = [
            [
                'name' => 'Aling Nena Store',
                'phone' => '0919-234-5678',
                'email' => 'alingnena@gmail.test',
                'address' => '142 Rizal St. corner Mabini, Poblacion',
                'barangay' => 'Poblacion',
                'area' => 'Zone 2',
                'jug_deposit' => 400.00,
                'avg_reorder_days' => 3,
                'last_order_date' => now()->subDays(4)->toDateString(),
                'jugs_held' => 5,
                'amount_owed' => 350.00,
                'credit_status' => 'Overdue',
                'refills' => 9,
            ],
            [
                'name' => 'Mang Kanor Carwash',
                'phone' => '0928-345-6789',
                'email' => 'mang.kanor@yahoo.test',
                'address' => 'National Highway near Petron, Brgy. Concepcion',
                'barangay' => 'Brgy. Concepcion',
                'area' => 'Zone 3',
                'jug_deposit' => 300.00,
                'avg_reorder_days' => 7,
                'last_order_date' => now()->subDays(8)->toDateString(),
                'jugs_held' => 4,
                'amount_owed' => 700.00,
                'credit_status' => 'Overdue',
                'refills' => 4,
            ],
        ];

        $extraCustomers = [];

        foreach ($additionalCustomers as $cData) {
            $cust = Customer::firstOrCreate(
                ['email' => $cData['email']],
                [
                    'name' => $cData['name'],
                    'phone' => $cData['phone'],
                    'address' => $cData['address'],
                    'barangay' => $cData['barangay'],
                    'area' => $cData['area'],
                    'jug_deposit' => $cData['jug_deposit'],
                    'status' => 'Active',
                    'avg_reorder_days' => $cData['avg_reorder_days'],
                    'last_order_date' => $cData['last_order_date'],
                ]
            );

            if (!$cust->jugLedger) {
                $cust->jugLedger()->create([
                    'customer_name' => $cust->name,
                    'jugs_held' => $cData['jugs_held'],
                    'jugs_borrowed_date' => $cData['last_order_date'],
                    'deposit_status' => $cData['jug_deposit'] > 0 ? 'Paid' : 'Unpaid',
                    'deposit_amount' => $cData['jug_deposit'],
                ]);
            }

            if (!$cust->creditLedger) {
                $cust->creditLedger()->create([
                    'customer_name' => $cust->name,
                    'amount_owed' => $cData['amount_owed'],
                    'status' => $cData['credit_status'],
                ]);
            }

            if (!$cust->loyaltyRecord) {
                $cust->loyaltyRecord()->create([
                    'customer_name' => $cust->name,
                    'refills_count' => $cData['refills'],
                    'refills_needed' => 10,
                    'free_jugs_earned' => 0,
                ]);
            }

            $extraCustomers[] = $cust;
        }

        // 7. Deterministic Orders & Deliveries (Demonstrating Pending, Assigned, En Route, Delivered)
        // Exactly 4 orders demonstrating each delivery lifecycle state
        $demoOrders = [
            // Order 1: Pending Order / Pending Delivery (Customer 1 - Maria Clara)
            [
                'customer' => $customer1,
                'date' => now()->toDateString(),
                'status' => 'Pending',
                'type' => 'Online',
                'jug_count' => 2,
                'round_count' => 2,
                'flat_count' => 0,
                'gallon_type' => 'Round',
                'unit_price' => 35.00,
                'round_unit_price' => 35.00,
                'flat_unit_price' => 40.00,
                'total' => 70.00,
                'payment_method' => 'Cash',
                'payment_status' => 'Unpaid',
                'rider' => null,
                'route_order' => 99,
                'delivery_status' => 'Pending',
            ],
            // Order 2: Confirmed Order / Assigned Delivery (Customer 2 - Juanito Pelaez)
            [
                'customer' => $customer2,
                'date' => now()->toDateString(),
                'status' => 'Confirmed',
                'type' => 'Phone',
                'jug_count' => 3,
                'round_count' => 0,
                'flat_count' => 3,
                'gallon_type' => 'Flat',
                'unit_price' => 40.00,
                'round_unit_price' => 35.00,
                'flat_unit_price' => 40.00,
                'total' => 120.00,
                'payment_method' => 'Cash',
                'payment_status' => 'Unpaid',
                'rider' => $rider1,
                'route_order' => 1,
                'delivery_status' => 'Assigned',
            ],
            // Order 3: Out for Delivery / En Route Delivery (Customer 3 - Aling Nena Store, Mixed Load)
            [
                'customer' => $extraCustomers[0],
                'date' => now()->toDateString(),
                'status' => 'Out for Delivery',
                'type' => 'Phone',
                'jug_count' => 4,
                'round_count' => 2,
                'flat_count' => 2,
                'gallon_type' => 'Mixed',
                'unit_price' => 37.50,
                'round_unit_price' => 35.00,
                'flat_unit_price' => 40.00,
                'total' => 150.00,
                'payment_method' => 'GCash',
                'payment_status' => 'Paid',
                'rider' => $rider1,
                'route_order' => 2,
                'delivery_status' => 'En Route',
            ],
            // Order 4: Delivered Order / Delivered Delivery (Customer 4 - Mang Kanor Carwash)
            [
                'customer' => $extraCustomers[1],
                'date' => now()->subDay()->toDateString(),
                'status' => 'Delivered',
                'type' => 'Walk-in',
                'jug_count' => 4,
                'round_count' => 4,
                'flat_count' => 0,
                'gallon_type' => 'Round',
                'unit_price' => 35.00,
                'round_unit_price' => 35.00,
                'flat_unit_price' => 40.00,
                'total' => 140.00,
                'payment_method' => 'Cash',
                'payment_status' => 'Paid',
                'rider' => $rider2,
                'route_order' => 1,
                'delivery_status' => 'Delivered',
            ],
        ];

        if (Order::count() === 0) {
            foreach ($demoOrders as $o) {
                $order = Order::create([
                    'customer_id' => $o['customer']->id,
                    'customer_name' => $o['customer']->name,
                    'order_date' => $o['date'],
                    'status' => $o['status'],
                    'type' => $o['type'],
                    'jug_count' => $o['jug_count'],
                    'round_count' => $o['round_count'],
                    'flat_count' => $o['flat_count'],
                    'gallon_type' => $o['gallon_type'],
                    'unit_price' => $o['unit_price'],
                    'round_unit_price' => $o['round_unit_price'],
                    'flat_unit_price' => $o['flat_unit_price'],
                    'total_amount' => $o['total'],
                    'payment_status' => $o['payment_status'],
                    'payment_method' => $o['payment_method'],
                    'delivery_address' => $o['customer']->address,
                    'rider_id' => $o['rider']?->id,
                    'rider_name' => $o['rider']?->name,
                    'route_order' => $o['route_order'] ?? 99,
                ]);

                Delivery::create([
                    'order_id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $o['customer']->phone,
                    'address' => $order->delivery_address,
                    'area' => $o['customer']->area,
                    'rider_id' => $o['rider']?->id,
                    'rider_name' => $o['rider']?->name,
                    'route_order' => $o['route_order'] ?? 99,
                    'status' => $o['delivery_status'],
                    'delivery_date' => $o['delivery_status'] === 'Delivered' ? $o['date'] : null,
                    'jug_count' => $order->jug_count,
                    'round_count' => $order->round_count,
                    'flat_count' => $order->flat_count,
                    'gallon_type' => $order->gallon_type,
                ]);

                if ($o['delivery_status'] === 'Delivered') {
                    Transaction::create([
                        'type' => 'income',
                        'category' => 'Water Sales',
                        'amount' => $order->total_amount,
                        'date' => $order->order_date,
                        'description' => "Order #{$order->id} ({$order->gallon_type} Gallon x {$order->jug_count}) - {$order->customer_name}",
                        'order_id' => $order->id,
                        'payment_method' => $order->payment_method,
                    ]);
                }
            }
        }

        // 8. Essential Financial Transactions (Clean & Small: 1 Walk-in Refill Sales + 3 Essential Operational Expenses)
        if (Transaction::whereNull('order_id')->count() === 0) {
            // Additional Station Income
            Transaction::create([
                'type' => 'income',
                'category' => 'Water Sales',
                'amount' => 1450.00,
                'date' => now()->toDateString(),
                'description' => 'Daily Walk-in Station Refill Sales',
                'payment_method' => 'Cash',
            ]);

            // Essential Operating Expenses
            Transaction::create([
                'type' => 'expense',
                'category' => 'Electricity',
                'amount' => 4200.00,
                'date' => now()->subDays(10)->toDateString(),
                'description' => 'Meralco Commercial Power Utility Bill',
                'payment_method' => 'Bank Transfer',
            ]);

            Transaction::create([
                'type' => 'expense',
                'category' => 'Water Bill',
                'amount' => 1850.00,
                'date' => now()->subDays(8)->toDateString(),
                'description' => 'Local Water District Utility',
                'payment_method' => 'Cash',
            ]);

            Transaction::create([
                'type' => 'expense',
                'category' => 'Supplies',
                'amount' => 1500.00,
                'date' => now()->subDays(4)->toDateString(),
                'description' => 'Non-spill caps and heat shrink seals station supply',
                'payment_method' => 'GCash',
            ]);
        }

        // 9. Essential Inventory Items (with 2 realistic low-stock warnings)
        $inventory = [
            [
                'name' => 'Round 5-Gallon Polycarbonate Jugs',
                'category' => 'Gallons',
                'quantity' => 120,
                'unit' => 'pcs',
                'reorder_threshold' => 30,
                'cost' => 180.00,
                'restocked' => now()->subDays(10)->toDateString(),
            ],
            [
                'name' => 'Slim / Flat 5-Gallon Blue Containers',
                'category' => 'Gallons',
                'quantity' => 85,
                'unit' => 'pcs',
                'reorder_threshold' => 25,
                'cost' => 195.00,
                'restocked' => now()->subDays(12)->toDateString(),
            ],
            [
                'name' => 'Non-Spill Blue Gallon Caps',
                'category' => 'Caps',
                'quantity' => 8, // Low Stock Warning
                'unit' => 'bags (100pcs)',
                'reorder_threshold' => 20,
                'cost' => 120.00,
                'restocked' => now()->subDays(30)->toDateString(),
            ],
            [
                'name' => 'Heat Shrink Gallon Neck Seals',
                'category' => 'Seals',
                'quantity' => 15, // Low Stock Warning
                'unit' => 'packs (500pcs)',
                'reorder_threshold' => 25,
                'cost' => 85.00,
                'restocked' => now()->subDays(25)->toDateString(),
            ],
            [
                'name' => '10-Micron PP Sediment Filters',
                'category' => 'Filters',
                'quantity' => 18,
                'unit' => 'pcs',
                'reorder_threshold' => 5,
                'cost' => 150.00,
                'restocked' => now()->subDays(15)->toDateString(),
            ],
        ];

        foreach ($inventory as $inv) {
            InventoryItem::firstOrCreate(
                ['name' => $inv['name']],
                [
                    'category' => $inv['category'],
                    'quantity' => $inv['quantity'],
                    'unit' => $inv['unit'],
                    'reorder_threshold' => $inv['reorder_threshold'],
                    'cost_per_unit' => $inv['cost'],
                    'last_restocked' => $inv['restocked'],
                ]
            );
        }

        // 10. Maintenance Logs (1 Normal, 1 Due Soon / Overdue)
        MaintenanceLog::firstOrCreate(
            ['equipment_name' => 'UV Sterilizer 12 GPM System'],
            [
                'equipment_type' => 'UV Sterilizer',
                'install_date' => now()->subMonths(8)->toDateString(),
                'last_service_date' => now()->subDays(30)->toDateString(),
                'next_service_date' => now()->addDays(60)->toDateString(),
                'service_interval_days' => 90,
                'volume_processed' => '32,100 Gallons',
                'notes' => 'Quartz sleeve cleaned and UV lamp ballast operational.',
            ]
        );

        MaintenanceLog::firstOrCreate(
            ['equipment_name' => 'Reverse Osmosis (RO) Membrane 4040'],
            [
                'equipment_type' => 'RO Membrane',
                'install_date' => now()->subMonths(14)->toDateString(),
                'last_service_date' => now()->subDays(95)->toDateString(),
                'next_service_date' => now()->subDays(5)->toDateString(), // Overdue
                'service_interval_days' => 90,
                'volume_processed' => '45,200 Gallons',
                'notes' => 'Membrane flushing and TDS monitoring required.',
            ]
        );

        // 11. Feedback Records (2 items: 1 Resolved, 1 In Progress)
        Feedback::firstOrCreate(
            ['customer_name' => $customer1->name],
            [
                'customer_id' => $customer1->id,
                'type' => 'Feedback',
                'rating' => 5,
                'message' => 'Laging napakabilis mag-deliver ni Kuya Mark! Napakalamig at malinis ang lasa ng tubig.',
                'status' => 'Resolved',
                'admin_response' => 'Maraming salamat po Ma\'am Maria! Ipararating po namin kay Kuya Mark.',
            ]
        );

        Feedback::firstOrCreate(
            ['customer_name' => $extraCustomers[0]->name],
            [
                'customer_id' => $extraCustomers[0]->id,
                'type' => 'Complaint',
                'rating' => 3,
                'message' => 'May konting tagas yung isang flat gallon cap kanina nung nilapag sa tindahan. Paki-check po yung seals.',
                'status' => 'In Progress',
                'admin_response' => 'Pasensya na po Aling Nena. Pinalitan po namin ng bagong cap.',
            ]
        );

        // 12. Minimal Initial Activity Logs
        if (ActivityLog::count() === 0) {
            ActivityLog::create([
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'user_role' => 'admin',
                'action' => 'System Initialized',
                'entity_type' => 'System',
                'entity_id' => null,
                'description' => 'Two J’s AquaTrack station management system initialized with default pricing: Round Gallon @ ₱35.00, Flat Gallon @ ₱40.00',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Console Seeder',
            ]);

            ActivityLog::create([
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'user_role' => 'admin',
                'action' => 'Rider Assigned',
                'entity_type' => 'Order',
                'entity_id' => 2,
                'description' => 'Assigned Kuya Mark Santos to Order #2 for Juanito Pelaez',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Console Seeder',
            ]);
        }
    }
}
