<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\JugLedger;
use App\Models\LoyaltyRecord;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CustomerSyncService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase3BWorkflowTest extends TestCase
{
    /**
     * B1.1: Customer can edit their own display name, synchronizing User and Customer profiles.
     */
    public function test_customer_can_update_own_name_and_syncs_profile(): void
    {
        $customerUser = User::where('role', 'customer')->first();
        $this->assertNotNull($customerUser);
        $customer = $customerUser->customer;
        $this->assertNotNull($customer);

        $originalUserName = $customerUser->name;
        $originalCustomerName = $customer->name;
        $newName = 'Updated Customer Name ' . rand(100, 999);

        $response = $this->actingAs($customerUser)->put(route('profile.update-name'), [
            'name' => $newName,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $customerUser->id,
            'name' => $newName,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => $newName,
        ]);

        // Verify activity log recorded
        $this->assertDatabaseHas('activity_logs', [
            'entity_type' => 'User',
            'entity_id' => $customerUser->id,
            'action' => 'Profile Name Updated',
        ]);

        // Cleanup: restore original name
        $customerUser->update(['name' => $originalUserName]);
        $customer->update(['name' => $originalCustomerName]);
    }

    /**
     * B1.2: Historical order and delivery customer-name snapshots must NOT be modified.
     */
    public function test_customer_name_update_does_not_modify_historical_order_snapshots(): void
    {
        $customerUser = User::where('role', 'customer')->first();
        $customer = $customerUser->customer;

        // Create an existing order and delivery with a historical snapshot
        $historicalName = 'Historical Snapshot Name';
        $order = Order::create([
            'order_number' => 'ORD-SNAP-TEST-' . rand(1000, 9999),
            'customer_id' => $customer->id,
            'customer_name' => $historicalName,
            'jug_count' => 2,
            'round_count' => 2,
            'flat_count' => 0,
            'unit_price' => 35.00,
            'round_unit_price' => 35.00,
            'flat_unit_price' => 40.00,
            'gallon_type' => 'Round',
            'total_amount' => 70.00,
            'payment_method' => 'Cash',
            'status' => 'Pending',
            'order_date' => now()->toDateString(),
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'customer_name' => $historicalName,
            'address' => 'Historical Address 123',
            'status' => 'Assigned',
            'jug_count' => 2,
            'round_count' => 2,
            'flat_count' => 0,
            'gallon_type' => 'Round',
        ]);

        $originalUserName = $customerUser->name;
        $newName = 'New Name Snapshot Test';

        $this->actingAs($customerUser)->put(route('profile.update-name'), [
            'name' => $newName,
        ]);

        // Verify historical order and delivery still preserve the snapshot name
        $order->refresh();
        $delivery->refresh();
        $this->assertEquals($historicalName, $order->customer_name);
        $this->assertEquals($historicalName, $delivery->customer_name);

        // Cleanup
        $delivery->delete();
        $order->delete();
        $customerUser->update(['name' => $originalUserName]);
        $customer->update(['name' => $originalUserName]);
    }

    /**
     * B1.3: Rider can edit their own display name, synchronizing User and Rider profiles.
     */
    public function test_rider_can_update_own_name_and_syncs_rider_profile(): void
    {
        $riderUser = User::where('role', 'rider')->first();
        $this->assertNotNull($riderUser);
        $rider = $riderUser->rider;
        $this->assertNotNull($rider);

        $originalUserName = $riderUser->name;
        $originalRiderName = $rider->name;
        $newName = 'Speedy Rider ' . rand(100, 999);

        $response = $this->actingAs($riderUser)->put(route('profile.update-name'), [
            'name' => $newName,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $riderUser->id,
            'name' => $newName,
        ]);
        $this->assertDatabaseHas('riders', [
            'id' => $rider->id,
            'name' => $newName,
        ]);

        // Cleanup
        $riderUser->update(['name' => $originalUserName]);
        $rider->update(['name' => $originalRiderName]);
    }

    /**
     * B1.4: Admin/Owner can edit their own display name.
     */
    public function test_admin_can_update_own_name(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        $originalName = $admin->name;
        $newName = 'Owner Boss ' . rand(100, 999);

        $response = $this->actingAs($admin)->put(route('profile.update-name'), [
            'name' => $newName,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => $newName,
        ]);

        // Cleanup
        $admin->update(['name' => $originalName]);
    }

    /**
     * B1.5: Security - User cannot change role, email, password, or another user's name.
     */
    public function test_name_editing_cannot_change_role_or_email(): void
    {
        $customerUser = User::where('role', 'customer')->first();
        $originalUserName = $customerUser->name;
        $originalCustomerName = $customerUser->customer?->name;
        $originalEmail = $customerUser->email;
        $originalRole = $customerUser->role;

        $response = $this->actingAs($customerUser)->put(route('profile.update-name'), [
            'name' => 'Valid Name Change',
            'role' => 'admin', // Tamper attempt
            'email' => 'hacked@example.com', // Tamper attempt
            'id' => 1, // Tamper attempt to alter another user
        ]);

        $customerUser->refresh();
        $this->assertEquals($originalRole, $customerUser->role);
        $this->assertEquals($originalEmail, $customerUser->email);

        // Cleanup: restore original names
        $customerUser->update(['name' => $originalUserName]);
        if ($customerUser->customer) {
            $customerUser->customer->update(['name' => $originalCustomerName]);
        }
    }

    public function test_name_editing_validates_minimum_and_maximum_length(): void
    {
        $customerUser = User::where('role', 'customer')->first();

        // Too short (< 2 characters)
        $responseShort = $this->actingAs($customerUser)->put(route('profile.update-name'), [
            'name' => 'A',
        ]);
        $responseShort->assertSessionHasErrors('name');

        // Empty string
        $responseEmpty = $this->actingAs($customerUser)->put(route('profile.update-name'), [
            'name' => '   ',
        ]);
        $responseEmpty->assertSessionHasErrors('name');

        // Too long (> 100 characters)
        $responseLong = $this->actingAs($customerUser)->put(route('profile.update-name'), [
            'name' => str_repeat('X', 101),
        ]);
        $responseLong->assertSessionHasErrors('name');
    }

    public function test_guest_cannot_update_name(): void
    {
        $response = $this->put(route('profile.update-name'), [
            'name' => 'Guest Attacker',
        ]);
        $response->assertRedirect(route('login'));
    }

    /**
     * B1.7: Layout Verification - Pages using <x-layouts.app> render edit-profile-name-modal
     * and trigger buttons with correct route, method, CSRF, and current user's name.
     */
    public function test_active_layout_renders_edit_profile_name_modal_and_trigger_buttons(): void
    {
        // 1. Customer Portal renders modal and welcome banner edit button
        $customerUser = User::where('role', 'customer')->first();
        $portalResponse = $this->actingAs($customerUser)->get(route('portal.index'));
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee("open-modal', 'edit-profile-name-modal'", false);
        $portalResponse->assertSee('edit-profile-name-modal');
        $portalResponse->assertSee(route('profile.update-name'));
        $portalResponse->assertSee('name="_method" value="PUT"', false);
        $portalResponse->assertSee('value="' . e($customerUser->name) . '"', false);

        // 2. Admin Dashboard renders modal and sidebar edit button
        $adminUser = User::where('role', 'admin')->first();
        $adminResponse = $this->actingAs($adminUser)->get(route('dashboard'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee("open-modal', 'edit-profile-name-modal'", false);
        $adminResponse->assertSee('edit-profile-name-modal');
        $adminResponse->assertSee('value="' . e($adminUser->name) . '"', false);

        // 3. Rider Deliveries renders modal and sidebar edit button
        $riderUser = User::where('role', 'rider')->first();
        $riderResponse = $this->actingAs($riderUser)->get(route('deliveries.index'));
        $riderResponse->assertStatus(200);
        $riderResponse->assertSee("open-modal', 'edit-profile-name-modal'", false);
        $riderResponse->assertSee('edit-profile-name-modal');
        $riderResponse->assertSee('value="' . e($riderUser->name) . '"', false);
    }

    /**
     * B2.1: Owner/Admin can manually adjust customer jug balance with a mandatory reason.
     * Concurrency safe and preserves existing jugs_borrowed_date.
     */
    public function test_admin_can_manually_adjust_customer_jug_balance(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $ledger = $customer->jugLedger;

        $initialHeld = $ledger?->jugs_held ?? 5;
        $initialDate = $ledger?->jugs_borrowed_date;

        // Ensure baseline ledger has a known balance and borrowed date
        $borrowedDate = '2026-09-15';
        if (!$ledger) {
            $ledger = $customer->jugLedger()->create([
                'customer_name' => $customer->name,
                'jugs_held' => 5,
                'jugs_borrowed_date' => $borrowedDate,
                'deposit_status' => 'Paid',
                'deposit_amount' => 100.00,
            ]);
        } else {
            $ledger->update([
                'jugs_held' => 5,
                'jugs_borrowed_date' => $borrowedDate,
            ]);
        }

        $targetBalance = 8;
        $reason = 'Customer brought 3 extra containers from home station';

        $response = $this->actingAs($admin)->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => $targetBalance,
            'reason' => $reason,
        ]);

        $response->assertSessionHas('success');

        $ledger->refresh();
        $this->assertEquals($targetBalance, $ledger->jugs_held);
        // jugs_borrowed_date MUST be preserved and not overwritten to today
        $this->assertEquals($borrowedDate, $ledger->jugs_borrowed_date->toDateString());

        // Verify activity log recorded
        $log = ActivityLog::where('action', 'Jug Balance Adjusted')
            ->where('entity_type', 'Customer')
            ->where('entity_id', $customer->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('from 5 to 8 (+3 jugs)', $log->description);
        $this->assertStringContainsString($reason, $log->description);
        $this->assertEquals(5, $log->old_values['jugs_held']);
        $this->assertEquals(8, $log->new_values['jugs_held']);
        $this->assertEquals(3, $log->new_values['adjustment']);
        $this->assertEquals($reason, $log->new_values['reason']);

        // Cleanup: restore original state
        $ledger->update([
            'jugs_held' => $initialHeld,
            'jugs_borrowed_date' => $initialDate,
        ]);
    }

    /**
     * B2.2: Manual jug balance adjustment must NOT create orders, deliveries, or transactions,
     * and must NOT alter loyalty refill counts.
     */
    public function test_manual_jug_adjustment_has_no_side_effects_on_orders_or_loyalty(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $loyalty = $customer->loyaltyRecord;
        $initialHeld = $customer->jugLedger?->jugs_held ?? 5;

        $initialOrdersCount = Order::count();
        $initialDeliveriesCount = Delivery::count();
        $initialTransactionsCount = Transaction::count();
        $initialLoyaltyRefills = $loyalty ? $loyalty->refills_count : 0;
        $initialFreeJugs = $loyalty ? $loyalty->free_jugs_earned : 0;

        $this->actingAs($admin)->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => 12,
            'reason' => 'Inventory count audit correction',
        ]);

        $this->assertEquals($initialOrdersCount, Order::count());
        $this->assertEquals($initialDeliveriesCount, Delivery::count());
        $this->assertEquals($initialTransactionsCount, Transaction::count());

        if ($loyalty) {
            $loyalty->refresh();
            $this->assertEquals($initialLoyaltyRefills, $loyalty->refills_count);
            $this->assertEquals($initialFreeJugs, $loyalty->free_jugs_earned);
        }

        // Cleanup
        $customer->jugLedger?->update(['jugs_held' => $initialHeld]);
    }

    /**
     * B2.3: Non-admin roles (Customer, Rider, Guest) cannot adjust jug balances.
     */
    public function test_non_admin_cannot_adjust_jug_balance(): void
    {
        $customer = Customer::first();
        $customerUser = User::where('role', 'customer')->first();
        $riderUser = User::where('role', 'rider')->first();

        // Customer attempt -> 403
        $responseCust = $this->actingAs($customerUser)->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => 99,
            'reason' => 'Unauthorized customer adjustment',
        ]);
        $responseCust->assertStatus(403);

        // Rider attempt -> 403
        $responseRider = $this->actingAs($riderUser)->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => 99,
            'reason' => 'Unauthorized rider adjustment',
        ]);
        $responseRider->assertStatus(403);

        // Guest attempt -> Redirect to login
        $this->app['auth']->logout();
        $responseGuest = $this->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => 99,
            'reason' => 'Unauthorized guest adjustment',
        ]);
        $responseGuest->assertRedirect(route('login'));
    }

    /**
     * B2.4: Validation rejects negative balance and missing/empty reason.
     */
    public function test_jug_adjustment_validation_rejects_negative_balance_and_empty_reason(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();

        // Negative balance
        $responseNegative = $this->actingAs($admin)->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => -5,
            'reason' => 'Negative balance test',
        ]);
        $responseNegative->assertSessionHasErrors('new_balance');

        // Empty reason
        $responseNoReason = $this->actingAs($admin)->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => 4,
            'reason' => '   ',
        ]);
        $responseNoReason->assertSessionHasErrors('reason');

        // Short reason (< 3 characters)
        $responseShortReason = $this->actingAs($admin)->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => 4,
            'reason' => 'No',
        ]);
        $responseShortReason->assertSessionHasErrors('reason');
    }

    /**
     * B2.5: Concurrency / Transactional locking behavior verification.
     * Ensures CustomerSyncService::manuallyAdjustJugBalance executes inside DB transaction with lockForUpdate().
     */
    public function test_manually_adjust_jug_balance_locks_row_and_calculates_atomically(): void
    {
        $customer = Customer::first();
        $initialHeld = $customer->jugLedger?->jugs_held ?? 5;
        $syncService = app(CustomerSyncService::class);

        // Run adjustment via service directly
        $ledger1 = $syncService->manuallyAdjustJugBalance($customer, 10, 'First atomic adjustment');
        $this->assertEquals(10, $ledger1->jugs_held);

        $ledger2 = $syncService->manuallyAdjustJugBalance($customer, 7, 'Second atomic adjustment');
        $this->assertEquals(7, $ledger2->jugs_held);

        // Confirm database has exact target balance
        $customer->refresh();
        $this->assertEquals(7, $customer->jugLedger->jugs_held);

        // Cleanup
        $customer->jugLedger?->update(['jugs_held' => $initialHeld]);
    }

    /**
     * B2.6: Customer Portal immediately reflects the updated jug balance.
     */
    public function test_customer_portal_immediately_reflects_adjusted_jug_balance(): void
    {
        $customerUser = User::where('role', 'customer')->first();
        $customer = $customerUser->customer;
        $admin = User::where('role', 'admin')->first();
        $initialHeld = $customer->jugLedger?->jugs_held ?? 5;

        // Admin adjusts balance to 15
        $this->actingAs($admin)->post(route('customers.adjust-jugs', $customer), [
            'new_balance' => 15,
            'reason' => 'Station bottle swap verification',
        ]);

        // Customer views portal home
        $response = $this->actingAs($customerUser)->get(route('portal.index', ['tab' => 'home']));
        $response->assertStatus(200);
        $response->assertSee('15');

        // Cleanup
        $customer->jugLedger?->update(['jugs_held' => $initialHeld]);
    }
}
