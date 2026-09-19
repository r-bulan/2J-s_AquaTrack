# Two J’s AquaTrack — Water Station Management System

A complete, production-ready web application built with **Laravel 11**, **MySQL**, **Blade**, **Tailwind CSS**, and **Alpine.js** for **Two J’s Water Station / Two J’s Water Filling Station**.

---

## 🚀 Key Features & Architectural Enhancements

1. **Role-Based Access Control**:
   - Strictly three roles: `admin`, `rider`, and `customer`.
   - Complete removal of the `staff` role; replaced with a dedicated `Riders` management module.
   - Enforced by server-side middleware (`EnsureRole`) and policies (`OrderPolicy`, `DeliveryPolicy`, `FeedbackPolicy`).

2. **Gallon Pricing Engine**:
   - Separate, dynamically configurable prices for **Round Gallon** (default ₱35.00) and **Flat Gallon** (default ₱40.00).
   - Historical pricing snapshot: Existing orders preserve their original calculated total even when prices change.

3. **End-to-End Refill & Dispatch Workflow**:
   - Order placement (Walk-in, Phone, Online via Customer Portal).
   - Rider assignment with routing sequence order.
   - Real proof-of-delivery file storage (upload photos & touch/mouse base64 canvas digital signatures).
   - Status sequence: `Pending` → `Confirmed` → `Out for Delivery` (`En Route`) → `Delivered`.

4. **Idempotent Financial Synchronization**:
   - Marking a delivery as `Delivered` automatically records a single `Water Sales` income transaction in the financial ledger.
   - Prevents duplicate accounting entries.

5. **Customer Relationship & Ledger Sync**:
   - Tracks customer jug holdings, deposits, credit (utang), and loyalty refill milestones (10 refills = 1 free jug).
   - Smart reorder alerts calculated as `daysSince(last_order_date) >= avg_reorder_days - 1`.

6. **Inventory & Equipment Maintenance**:
   - Real-time stock tracking with low-stock warnings (`quantity <= reorder_threshold`) and restock logging.
   - Equipment service tracking (RO Membrane, UV Sterilizer, Filters, Pumps) with 14-day warning flags and overdue notifications.

7. **BIR-Style Reports & Data Exports**:
   - Rolling 7-day, 30-day, and 365-day accounting summaries.
   - Server-side PDF generation (`barryvdh/laravel-dompdf`) named `twojs_report_YYYYMMDD.pdf`.
   - Instant CSV exports for Sales and Expenses.

8. **Activity / Audit Logs**:
   - Full event history for admin/owner with IP addresses, user agents, actions, and before/after changes.

---

## 🛠️ Technology Stack

- **Framework**: Laravel 11.x (PHP 8.2+)
- **Database**: MySQL (XAMPP / MariaDB)
- **Frontend**: Laravel Blade, Tailwind CSS 3.x, Alpine.js, Chart.js
- **PDF Generation**: `barryvdh/laravel-dompdf`
- **Asset Bundling**: Vite

---

## 👥 Seeded Development Accounts

All development accounts use the password: `Password123!`

| Role | Email | Redirect Landing | Module Access |
| :--- | :--- | :--- | :--- |
| **Admin / Owner** | `admin@twojs.test` | `/dashboard` | Unrestricted access to all 12 management modules & settings |
| **Delivery Rider** | `rider@twojs.test` | `/deliveries` | Rider dispatch queue, route viewer, POD upload/signature |
| **Customer** | `customer@twojs.test` | `/portal` | Customer portal: refill order, loyalty tracker, feedback |

---

## 💻 Local Setup & Execution

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL running (e.g. via XAMPP on port 3306)

### Installation Steps

1. **Clone and Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Environment Configuration**:
   Ensure `.env` has MySQL credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=twojs_aquatrack
   DB_USERNAME=root
   DB_PASSWORD=
   FILESYSTEM_DISK=public
   ```

3. **Storage Symlink**:
   ```bash
   php artisan storage:link
   ```

4. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Build Frontend Assets**:
   ```bash
   npm run build
   ```

6. **Start Local Development Server**:
   ```bash
   php artisan serve
   ```
   Open your browser at `http://127.0.0.1:8000`.

---

## 🧪 Automated Testing

To run the complete automated test suite (17 tests, 102 assertions):
```bash
php artisan test
```

### Test Coverage Highlights:
- **`CriticalWorkflowTest`**: Tests full order creation, rider assignment, delivery completion, proof upload, and automatic financial synchronization.
- **`PriceTest`**: Verifies dynamic price calculation and historical price preservation.
- **`RoleAuthorizationTest`**: Verifies strict server-side 403 Forbidden checks for unauthorized customer and rider access.
- **`ReportExportTest`**: Validates server-side PDF generation and CSV exports.
- **`RootRedirectTest`**: Checks root `/` redirection by authenticated role.
- **`ViewRenderingTest`**: Validates HTTP 200 rendering for every Blade view across all user roles.
