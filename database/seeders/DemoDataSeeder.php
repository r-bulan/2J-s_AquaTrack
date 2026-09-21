<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Feedback;
use App\Models\InventoryItem;
use App\Models\MaintenanceLog;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * DemoDataSeeder (Category B: Optional Demo & Historical Dataset)
 *
 * This seeder generates multi-month financial history, extended customer populations,
 * and additional historical orders for visual dashboard demos and analytics reports.
 *
 * It is NOT executed by default in DatabaseSeeder.
 * Run manually via:
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $rider1 = Rider::first();
        $rider2 = Rider::skip(1)->first() ?? $rider1;

        // 1. Extended Customer Accounts
        $additionalCustomers = [
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
                'last_order_date' => now()->subDays(9)->toDateString(),
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
                'last_order_date' => now()->subDays(3)->toDateString(),
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
                'last_order_date' => now()->subDays(6)->toDateString(),
                'jugs_held' => 3,
                'amount_owed' => 0.00,
                'credit_status' => 'Settled',
                'refills' => 3,
            ],
        ];

        $createdExtCustomers = [];

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

            $createdExtCustomers[] = $cust;
        }

        // 2. Past 6 Months of Financial History (Monthly Utilities & Bi-Daily Refill Income)
        for ($m = 5; $m >= 0; $m--) {
            $monthDate = now()->subMonths($m)->startOfMonth();

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

        // 3. Additional Equipment & Inventory
        MaintenanceLog::firstOrCreate(
            ['equipment_name' => 'Multi-Media Silica Sand Pre-Filter Tank'],
            [
                'equipment_type' => 'Filter',
                'install_date' => now()->subMonths(18)->toDateString(),
                'last_service_date' => now()->subDays(20)->toDateString(),
                'next_service_date' => now()->addDays(70)->toDateString(),
                'service_interval_days' => 90,
                'volume_processed' => '58,000 Gallons',
                'notes' => 'Backwashed and rinsed successfully. Pressure gauge at 42 PSI.',
            ]
        );

        MaintenanceLog::firstOrCreate(
            ['equipment_name' => 'Stainless Steel Booster Pump 1.5HP'],
            [
                'equipment_type' => 'Pump',
                'install_date' => now()->subMonths(12)->toDateString(),
                'last_service_date' => now()->subDays(45)->toDateString(),
                'next_service_date' => now()->addDays(45)->toDateString(),
                'service_interval_days' => 90,
                'volume_processed' => '41,000 Gallons',
                'notes' => 'Impeller and mechanical seal inspected. Motor running smoothly without noise.',
            ]
        );

        InventoryItem::firstOrCreate(
            ['name' => 'Granular Activated Carbon (GAC) Cartridges'],
            [
                'category' => 'Filters',
                'quantity' => 12,
                'unit' => 'pcs',
                'reorder_threshold' => 4,
                'cost_per_unit' => 280.00,
                'last_restocked' => now()->subDays(15)->toDateString(),
            ]
        );

        InventoryItem::firstOrCreate(
            ['name' => 'Food Grade Station Sanitizer Solution'],
            [
                'category' => 'Chemicals',
                'quantity' => 6,
                'unit' => 'gallons',
                'reorder_threshold' => 2,
                'cost_per_unit' => 450.00,
                'last_restocked' => now()->subDays(8)->toDateString(),
            ]
        );

        if (!empty($createdExtCustomers)) {
            Feedback::firstOrCreate(
                ['customer_name' => $createdExtCustomers[1]->name],
                [
                    'customer_id' => $createdExtCustomers[1]->id,
                    'type' => 'Feedback',
                    'rating' => 5,
                    'message' => 'Very reliable water service for our clinic. Always on time and clean containers.',
                    'status' => 'Open',
                ]
            );
        }
    }
}
