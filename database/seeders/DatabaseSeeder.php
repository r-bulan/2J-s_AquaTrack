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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Settings
        Setting::set('round_gallon_price', '35.00');
        Setting::set('flat_gallon_price', '40.00');

        // 2. Admin User
        $admin = User::create([
            'name' => 'Juan Dela Cruz (Owner)',
            'email' => 'admin@twojs.test',
            'password' => Hash::make('Password123!'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // 3. Riders (Users + Rider Profiles)
        $riderUser1 = User::create([
            'name' => 'Kuya Mark Santos',
            'email' => 'rider@twojs.test',
            'password' => Hash::make('Password123!'),
            'role' => 'rider',
            'email_verified_at' => now(),
        ]);

        $rider1 = Rider::create([
            'user_id' => $riderUser1->id,
            'name' => 'Kuya Mark Santos',
            'phone' => '0918-123-4567',
            'status' => 'Active',
            'wage_rate' => 450.00,
            'cash_advance' => 500.00,
            'area' => 'Zone 1 & Zone 2',
            'vehicle' => 'Honda Wave 110 w/ Sidecar (Plate 123-ABC)',
        ]);

        $riderUser2 = User::create([
            'name' => 'Kuya Arnel Reyes',
            'email' => 'arnel.rider@twojs.test',
            'password' => Hash::make('Password123!'),
            'role' => 'rider',
            'email_verified_at' => now(),
        ]);

        $rider2 = Rider::create([
            'user_id' => $riderUser2->id,
            'name' => 'Kuya Arnel Reyes',
            'phone' => '0920-987-6543',
            'status' => 'Active',
            'wage_rate' => 450.00,
            'cash_advance' => 0.00,
            'area' => 'Zone 3 & Poblacion',
            'vehicle' => 'Rusi 125 Cargo Carrier (Plate 456-XYZ)',
        ]);

        // 4. Primary Demo Customer
        $customerUser1 = User::create([
            'name' => 'Maria Clara',
            'email' => 'customer@twojs.test',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $customer1 = Customer::create([
            'user_id' => $customerUser1->id,
            'name' => 'Maria Clara',
            'phone' => '0917-555-1234',
            'email' => 'customer@twojs.test',
            'address' => 'Blk 12 Lot 4 Sampaguita St., Brgy. San Antonio',
            'barangay' => 'Brgy. San Antonio',
            'area' => 'Zone 1',
            'jug_deposit' => 200.00,
            'status' => 'Active',
            'avg_reorder_days' => 5,
            'last_order_date' => now()->subDays(5)->toDateString(), // Due for refill!
            'notes' => 'Prefers morning delivery. Ring the gate bell twice.',
        ]);

        $customer1->jugLedger()->create([
            'customer_name' => $customer1->name,
            'jugs_held' => 3,
            'jugs_borrowed_date' => now()->subDays(5)->toDateString(),
            'deposit_status' => 'Paid',
            'deposit_amount' => 200.00,
        ]);

        $customer1->creditLedger()->create([
            'customer_name' => $customer1->name,
            'amount_owed' => 0.00,
            'status' => 'Settled',
        ]);

        $customer1->loyaltyRecord()->create([
            'customer_name' => $customer1->name,
            'refills_count' => 7,
            'refills_needed' => 10,
            'free_jugs_earned' => 1,
            'last_reward_date' => now()->subMonths(1)->toDateString(),
        ]);

        // 5. Additional Realistic Philippine Customers
        $sampleCustomers = [
            [
                'name' => 'Aling Nena Store',
                'phone' => '0919-234-5678',
                'email' => 'alingnena@gmail.test',
                'address' => '142 Rizal St. corner Mabini, Poblacion',
                'barangay' => 'Poblacion',
                'area' => 'Zone 2',
                'jug_deposit' => 400.00,
                'avg_reorder_days' => 3,
                'last_order_date' => now()->subDays(4)->toDateString(), // Due!
                'jugs_held' => 6,
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
                'last_order_date' => now()->subDays(8)->toDateString(), // Due!
                'jugs_held' => 4,
                'amount_owed' => 700.00,
                'credit_status' => 'Overdue',
                'refills' => 4,
            ],
            [
                'name' => 'Capt. Rico Morales',
                'phone' => '0939-456-7890',
                'email' => 'capt.rico@barangay.gov.test',
                'address' => 'Barangay Hall Complex, Brgy. San Antonio',
                'barangay' => 'Brgy. San Antonio',
                'area' => 'Zone 1',
                'jug_deposit' => 600.00,
                'avg_reorder_days' => 4,
                'last_order_date' => now()->subDays(1)->toDateString(),
                'jugs_held' => 10,
                'amount_owed' => 0.00,
                'credit_status' => 'Settled',
                'refills' => 2,
            ],
            [
                'name' => 'Dr. Elena Bautista Dental Clinic',
                'phone' => '0917-888-9999',
                'email' => 'elena.bautista@dental.test',
                'address' => '2nd Floor Med Plaza, Quezon Ave, Poblacion',
                'barangay' => 'Poblacion',
                'area' => 'Zone 2',
                'jug_deposit' => 200.00,
                'avg_reorder_days' => 10,
                'last_order_date' => now()->subDays(9)->toDateString(), // Due!
                'jugs_held' => 2,
                'amount_owed' => 0.00,
                'credit_status' => 'Settled',
                'refills' => 8,
            ],
            [
                'name' => 'Teacher Grace Villanueva',
                'phone' => '0995-111-2233',
                'email' => 'grace.v@deped.test',
                'address' => 'Phase 3 Block 8 Lot 15, Villa Mercedes Subd.',
                'barangay' => 'Brgy. Concepcion',
                'area' => 'Zone 3',
                'jug_deposit' => 200.00,
                'avg_reorder_days' => 6,
                'last_order_date' => now()->subDays(2)->toDateString(),
                'jugs_held' => 2,
                'amount_owed' => 0.00,
                'credit_status' => 'Settled',
                'refills' => 5,
            ],
            [
                'name' => 'Tito Sonny Bakery',
                'phone' => '0922-444-5566',
                'email' => 'titosonny@bakery.test',
                'address' => '88 Bonifacio St., Brgy. Balintawak',
                'barangay' => 'Brgy. Balintawak',
                'area' => 'Zone 1',
                'jug_deposit' => 500.00,
                'avg_reorder_days' => 3,
                'last_order_date' => now()->subDays(3)->toDateString(), // Due!
                'jugs_held' => 8,
                'amount_owed' => 150.00,
                'credit_status' => 'Outstanding',
                'refills' => 6,
            ],
            [
                'name' => 'Kagawad Benjie Cruz',
                'phone' => '0933-777-8899',
                'email' => 'benjie.cruz@sanantonio.test',
                'address' => 'Sitio Ilaya, Brgy. San Antonio',
                'barangay' => 'Brgy. San Antonio',
                'area' => 'Zone 1',
                'jug_deposit' => 200.00,
                'avg_reorder_days' => 7,
                'last_order_date' => now()->subDays(6)->toDateString(), // Due!
                'jugs_held' => 3,
                'amount_owed' => 0.00,
                'credit_status' => 'Settled',
                'refills' => 3,
            ],
        ];

        $createdCustomers = [$customer1];

        foreach ($sampleCustomers as $cData) {
            $cust = Customer::create([
                'name' => $cData['name'],
                'phone' => $cData['phone'],
                'email' => $cData['email'],
                'address' => $cData['address'],
                'barangay' => $cData['barangay'],
                'area' => $cData['area'],
                'jug_deposit' => $cData['jug_deposit'],
                'status' => 'Active',
                'avg_reorder_days' => $cData['avg_reorder_days'],
                'last_order_date' => $cData['last_order_date'],
            ]);

            $cust->jugLedger()->create([
                'customer_name' => $cust->name,
                'jugs_held' => $cData['jugs_held'],
                'jugs_borrowed_date' => $cData['last_order_date'],
                'deposit_status' => $cData['jug_deposit'] > 0 ? 'Paid' : 'Unpaid',
                'deposit_amount' => $cData['jug_deposit'],
            ]);

            $cust->creditLedger()->create([
                'customer_name' => $cust->name,
                'amount_owed' => $cData['amount_owed'],
                'status' => $cData['credit_status'],
            ]);

            $cust->loyaltyRecord()->create([
                'customer_name' => $cust->name,
                'refills_count' => $cData['refills'],
                'refills_needed' => 10,
                'free_jugs_earned' => 0,
            ]);

            $createdCustomers[] = $cust;
        }

        // 6. Orders & Deliveries
        $ordersData = [
            // Today's Active Route
            [
                'customer' => $customer1,
                'date' => now()->toDateString(),
                'status' => 'Out for Delivery',
                'type' => 'Online',
                'jug_count' => 3,
                'gallon_type' => 'Round',
                'unit_price' => 35.00,
                'total' => 105.00,
                'payment_method' => 'GCash',
                'payment_status' => 'Paid',
                'rider' => $rider1,
                'route_order' => 1,
                'delivery_status' => 'En Route',
            ],
            [
                'customer' => $createdCustomers[1], // Aling Nena
                'date' => now()->toDateString(),
                'status' => 'Confirmed',
                'type' => 'Phone',
                'jug_count' => 5,
                'gallon_type' => 'Flat',
                'unit_price' => 40.00,
                'total' => 200.00,
                'payment_method' => 'Cash',
                'payment_status' => 'Unpaid',
                'rider' => $rider1,
                'route_order' => 2,
                'delivery_status' => 'Assigned',
            ],
            [
                'customer' => $createdCustomers[2], // Mang Kanor
                'date' => now()->toDateString(),
                'status' => 'Confirmed',
                'type' => 'Walk-in',
                'jug_count' => 4,
                'gallon_type' => 'Round',
                'unit_price' => 35.00,
                'total' => 140.00,
                'payment_method' => 'Credit',
                'payment_status' => 'Credit',
                'rider' => $rider2,
                'route_order' => 1,
                'delivery_status' => 'Assigned',
            ],
            [
                'customer' => $createdCustomers[3], // Capt Rico
                'date' => now()->toDateString(),
                'status' => 'Delivered',
                'type' => 'Phone',
                'jug_count' => 10,
                'gallon_type' => 'Round',
                'unit_price' => 35.00,
                'total' => 350.00,
                'payment_method' => 'Cash',
                'payment_status' => 'Paid',
                'rider' => $rider1,
                'route_order' => 1,
                'delivery_status' => 'Delivered',
            ],
            // Past Orders over past 7 days
            [
                'customer' => $createdCustomers[4], // Dr Elena
                'date' => now()->subDays(1)->toDateString(),
                'status' => 'Delivered',
                'type' => 'Online',
                'jug_count' => 2,
                'gallon_type' => 'Flat',
                'unit_price' => 40.00,
                'total' => 80.00,
                'payment_method' => 'GCash',
                'payment_status' => 'Paid',
                'rider' => $rider2,
                'route_order' => 1,
                'delivery_status' => 'Delivered',
            ],
            [
                'customer' => $createdCustomers[5], // Teacher Grace
                'date' => now()->subDays(2)->toDateString(),
                'status' => 'Delivered',
                'type' => 'Online',
                'jug_count' => 3,
                'gallon_type' => 'Round',
                'unit_price' => 35.00,
                'total' => 105.00,
                'payment_method' => 'Cash',
                'payment_status' => 'Paid',
                'rider' => $rider1,
                'route_order' => 1,
                'delivery_status' => 'Delivered',
            ],
            [
                'customer' => $createdCustomers[6], // Tito Sonny
                'date' => now()->subDays(3)->toDateString(),
                'status' => 'Delivered',
                'type' => 'Phone',
                'jug_count' => 6,
                'gallon_type' => 'Round',
                'unit_price' => 35.00,
                'total' => 210.00,
                'payment_method' => 'Cash',
                'payment_status' => 'Paid',
                'rider' => $rider1,
                'route_order' => 2,
                'delivery_status' => 'Delivered',
            ],
            [
                'customer' => $createdCustomers[7], // Kagawad Benjie
                'date' => now()->subDays(4)->toDateString(),
                'status' => 'Delivered',
                'type' => 'Walk-in',
                'jug_count' => 4,
                'gallon_type' => 'Flat',
                'unit_price' => 40.00,
                'total' => 160.00,
                'payment_method' => 'Maya',
                'payment_status' => 'Paid',
                'rider' => $rider2,
                'route_order' => 1,
                'delivery_status' => 'Delivered',
            ],
            [
                'customer' => $customer1,
                'date' => now()->subDays(5)->toDateString(),
                'status' => 'Delivered',
                'type' => 'Online',
                'jug_count' => 3,
                'gallon_type' => 'Round',
                'unit_price' => 35.00,
                'total' => 105.00,
                'payment_method' => 'GCash',
                'payment_status' => 'Paid',
                'rider' => $rider1,
                'route_order' => 1,
                'delivery_status' => 'Delivered',
            ],
        ];

        foreach ($ordersData as $o) {
            $order = Order::create([
                'customer_id' => $o['customer']->id,
                'customer_name' => $o['customer']->name,
                'order_date' => $o['date'],
                'status' => $o['status'],
                'type' => $o['type'],
                'jug_count' => $o['jug_count'],
                'gallon_type' => $o['gallon_type'],
                'unit_price' => $o['unit_price'],
                'total_amount' => $o['total'],
                'payment_status' => $o['payment_status'],
                'payment_method' => $o['payment_method'],
                'delivery_address' => $o['customer']->address,
                'rider_id' => $o['rider']?->id,
                'rider_name' => $o['rider']?->name,
                'route_order' => $o['route_order'],
            ]);

            Delivery::create([
                'order_id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $o['customer']->phone,
                'address' => $order->delivery_address,
                'area' => $o['customer']->area,
                'rider_id' => $o['rider']?->id,
                'rider_name' => $o['rider']?->name,
                'route_order' => $o['route_order'],
                'status' => $o['delivery_status'],
                'delivery_date' => $o['delivery_status'] === 'Delivered' ? $o['date'] : null,
                'jug_count' => $order->jug_count,
                'gallon_type' => $order->gallon_type,
            ]);

            // If delivered, create corresponding Water Sales income transaction
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

        // 7. Seed Past 6 Months of Financial Transactions
        // Generate daily water sales and realistic monthly operational expenses
        for ($m = 5; $m >= 0; $m--) {
            $monthDate = now()->subMonths($m)->startOfMonth();

            // Recurring monthly station expenses
            Transaction::create([
                'type' => 'expense',
                'category' => 'Electricity',
                'amount' => rand(3800, 4800),
                'date' => $monthDate->copy()->addDays(15)->toDateString(),
                'description' => 'Meralco Commercial Power Bill - ' . $monthDate->format('F Y'),
                'payment_method' => 'Bank Transfer',
            ]);

            Transaction::create([
                'type' => 'expense',
                'category' => 'Water Bill',
                'amount' => rand(1500, 2200),
                'date' => $monthDate->copy()->addDays(18)->toDateString(),
                'description' => 'Local Water District Utility - ' . $monthDate->format('F Y'),
                'payment_method' => 'Cash',
            ]);

            Transaction::create([
                'type' => 'expense',
                'category' => 'Rider Wages',
                'amount' => 9000.00,
                'date' => $monthDate->copy()->addDays(15)->toDateString(),
                'description' => '1st Half Rider Payroll & Allowances',
                'payment_method' => 'Cash',
            ]);

            Transaction::create([
                'type' => 'expense',
                'category' => 'Rider Wages',
                'amount' => 9000.00,
                'date' => $monthDate->copy()->endOfMonth()->toDateString(),
                'description' => '2nd Half Rider Payroll & Allowances',
                'payment_method' => 'Cash',
            ]);

            Transaction::create([
                'type' => 'expense',
                'category' => 'Supplies',
                'amount' => rand(1200, 2500),
                'date' => $monthDate->copy()->addDays(5)->toDateString(),
                'description' => 'Caps, shrink seals, and sanitizing solutions bulk restock',
                'payment_method' => 'GCash',
            ]);

            // Sample periodic maintenance expense
            if ($m % 2 === 0) {
                Transaction::create([
                    'type' => 'expense',
                    'category' => 'Maintenance',
                    'amount' => 3500.00,
                    'date' => $monthDate->copy()->addDays(20)->toDateString(),
                    'description' => 'Sediment filter replacement and UV sterilizer bulb check',
                    'payment_method' => 'Cash',
                ]);
            }

            // Generate daily income entries for each week of that month
            for ($day = 1; $day <= 28; $day += 2) {
                $txDate = $monthDate->copy()->addDays($day);
                if ($txDate->gt(now())) break;

                $dailySales = rand(1200, 3200);
                Transaction::create([
                    'type' => 'income',
                    'category' => 'Water Sales',
                    'amount' => $dailySales,
                    'date' => $txDate->toDateString(),
                    'description' => 'Daily Walk-in and Refill Station Sales',
                    'payment_method' => 'Cash',
                ]);
            }
        }

        // 8. Inventory Items (with 2 low-stock items)
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
                'quantity' => 8, // LOW STOCK! Threshold 50
                'unit' => 'bags (100pcs)',
                'reorder_threshold' => 20,
                'cost' => 120.00,
                'restocked' => now()->subDays(30)->toDateString(),
            ],
            [
                'name' => 'Heat Shrink Gallon Neck Seals',
                'category' => 'Seals',
                'quantity' => 15, // LOW STOCK! Threshold 25
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
            [
                'name' => 'Granular Activated Carbon (GAC) Cartridges',
                'category' => 'Filters',
                'quantity' => 12,
                'unit' => 'pcs',
                'reorder_threshold' => 4,
                'cost' => 280.00,
                'restocked' => now()->subDays(15)->toDateString(),
            ],
            [
                'name' => 'Food Grade Station Sanitizer Solution',
                'category' => 'Chemicals',
                'quantity' => 6,
                'unit' => 'gallons',
                'reorder_threshold' => 2,
                'cost' => 450.00,
                'restocked' => now()->subDays(8)->toDateString(),
            ],
        ];

        foreach ($inventory as $inv) {
            InventoryItem::create([
                'name' => $inv['name'],
                'category' => $inv['category'],
                'quantity' => $inv['quantity'],
                'unit' => $inv['unit'],
                'reorder_threshold' => $inv['reorder_threshold'],
                'cost_per_unit' => $inv['cost'],
                'last_restocked' => $inv['restocked'],
            ]);
        }

        // 9. Maintenance Equipment (1 Overdue, 1 Due Soon, others Normal)
        MaintenanceLog::create([
            'equipment_name' => 'Reverse Osmosis (RO) Membrane 4040',
            'equipment_type' => 'RO Membrane',
            'install_date' => now()->subMonths(14)->toDateString(),
            'last_service_date' => now()->subDays(105)->toDateString(),
            'next_service_date' => now()->subDays(15)->toDateString(), // OVERDUE!
            'service_interval_days' => 90,
            'volume_processed' => '45,200 Gallons',
            'notes' => 'Membrane flushing and TDS monitoring required immediately.',
        ]);

        MaintenanceLog::create([
            'equipment_name' => 'UV Sterilizer 12 GPM System',
            'equipment_type' => 'UV Sterilizer',
            'install_date' => now()->subMonths(8)->toDateString(),
            'last_service_date' => now()->subDays(82)->toDateString(),
            'next_service_date' => now()->addDays(8)->toDateString(), // DUE SOON! (within 14 days)
            'service_interval_days' => 90,
            'volume_processed' => '32,100 Gallons',
            'notes' => 'Inspect quartz sleeve for scale buildup and check ballast voltage.',
        ]);

        MaintenanceLog::create([
            'equipment_name' => 'Multi-Media Silica Sand Pre-Filter Tank',
            'equipment_type' => 'Filter',
            'install_date' => now()->subMonths(18)->toDateString(),
            'last_service_date' => now()->subDays(20)->toDateString(),
            'next_service_date' => now()->addDays(70)->toDateString(),
            'service_interval_days' => 90,
            'volume_processed' => '58,000 Gallons',
            'notes' => 'Backwashed and rinsed successfully. Pressure gauge at 42 PSI.',
        ]);

        MaintenanceLog::create([
            'equipment_name' => 'Stainless Steel Booster Pump 1.5HP',
            'equipment_type' => 'Pump',
            'install_date' => now()->subMonths(12)->toDateString(),
            'last_service_date' => now()->subDays(45)->toDateString(),
            'next_service_date' => now()->addDays(45)->toDateString(),
            'service_interval_days' => 90,
            'volume_processed' => '41,000 Gallons',
            'notes' => 'Impeller and mechanical seal inspected. Motor running smoothly without noise.',
        ]);

        // 10. Feedback & Complaints
        Feedback::create([
            'customer_id' => $customer1->id,
            'customer_name' => $customer1->name,
            'type' => 'Feedback',
            'rating' => 5,
            'message' => 'Laging napakabilis mag-deliver ni Kuya Mark! Napakalamig at malinis ang lasa ng tubig. Salamat Two J\'s!',
            'status' => 'Resolved',
            'admin_response' => 'Maraming salamat po Ma\'am Maria! Ipararating po namin kay Kuya Mark.',
        ]);

        Feedback::create([
            'customer_id' => $createdCustomers[1]->id, // Aling Nena
            'customer_name' => $createdCustomers[1]->name,
            'type' => 'Complaint',
            'rating' => 3,
            'message' => 'May konting tagas yung isang flat gallon cap kanina nung nilapag sa tindahan. Paki-check po yung seals.',
            'status' => 'In Progress',
            'admin_response' => 'Pasensya na po Aling Nena. Pinalitan po namin ng bagong cap at papalitan namin ng bago sa susunod na delivery.',
        ]);

        Feedback::create([
            'customer_id' => $createdCustomers[4]->id, // Dr Elena
            'customer_name' => $createdCustomers[4]->name,
            'type' => 'Feedback',
            'rating' => 5,
            'message' => 'Very reliable water service for our clinic. Always on time and clean containers.',
            'status' => 'Open',
        ]);

        // 11. Initial Activity Logs
        ActivityLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'user_role' => 'admin',
            'action' => 'System Initialized',
            'entity_type' => 'System',
            'entity_id' => null,
            'description' => 'Two J’s AquaTrack station management system initialized with default pricing: Round Gallon @ ₱35.00, Flat Gallon @ ₱40.00',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'user_role' => 'admin',
            'action' => 'Rider Assigned',
            'entity_type' => 'Order',
            'entity_id' => 1,
            'description' => 'Assigned Kuya Mark Santos to Order #1 for Maria Clara',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);
    }
}
