# Warehouse Management System

A complete, production-ready, database-driven **Warehouse Management System** built with PHP 8+, MySQL 8+ / MariaDB, HTML5, CSS3, and Vanilla JavaScript. Engineered for high data integrity, transactional stock movement auditing, role-based access control, and seamless deployment on both local XAMPP and cPanel shared hosting environments.

---

## 1. Project Overview

The Warehouse Management System provides enterprise-grade inventory tracking, stock movements auditing (IN / OUT), real-time stock balances, low-stock threshold monitoring, printable movement reports, and comprehensive user role management.

The system features two distinct user roles:
1. **Admin**: Full administrative privileges over warehouse items, stock transactions (IN / OUT), historical reporting, printable summaries, and system user accounts.
2. **User**: Operational view privileges allowing warehouse staff to monitor inventory levels, bin locations, and historical stock movements without modification rights.

---

## 2. Technology Stack

- **Backend Language:** PHP 8+ (tested and compatible with PHP 8.0, 8.1, 8.2, 8.3, 8.4, and 8.5)
- **Database Engine:** MySQL 8+ / MariaDB with InnoDB Engine and `utf8mb4` Character Set
- **Database Access:** PHP Data Objects (PDO) with Prepared Statements
- **Transactions:** Atomic ACID Transactions (`beginTransaction`, `SELECT ... FOR UPDATE`, `commit`, `rollBack`)
- **Frontend Markup:** HTML5 (semantic and accessible)
- **Styling:** Pure CSS3 (custom responsive design, dark green theme, print-specific stylesheets; no external CSS frameworks)
- **Client Scripting:** Pure Vanilla JavaScript (no external libraries or build tools)
- **Development Environment:** XAMPP (Apache + MySQL/MariaDB)
- **Production Environment:** cPanel Shared Hosting (Apache + MySQL/MariaDB)

---

## 3. System Features

### A. Authentication & Session Management
- Database-backed user authentication using standard `password_hash()` and `password_verify()`.
- Session security with automatic ID regeneration (`session_regenerate_id(true)`) upon sign-in.
- CSRF protection across all state-changing POST forms.
- Automatic routing based on assigned role.
- Unauthenticated access restrictions and strict URL-tamper protection for administrative routes.

### B. Admin Dashboard (Module 01)
- **Five Dynamic Stat Cards:**
  1. Total Items (`COUNT(*)`)
  2. Total Stock Quantity (`COALESCE(SUM(quantity), 0)`)
  3. Low Stock Items (`quantity <= reorder_level`)
  4. Today's Stock IN (`movement_type = 'IN'` on current date)
  5. Today's Stock OUT (`movement_type = 'OUT'` on current date)
- **Low Stock Table:** Immediately highlights items whose quantity is at or below the reorder threshold with alert badges. If no items are below threshold, displays `"No low-stock items right now."`.

### C. Item Management (Module 02)
- Add, Edit, Delete, View, Search, and Filter warehouse items.
- Item properties: Item Code (unique, automatically converted to uppercase), Item Name, Category, Location, Quantity, Unit of Measure, Reorder Level.
- Dynamic filtering by category dropdown and search across Item Code, Item Name, and Location.
- Item Detail modal displaying full metadata and the **latest 20 stock movement transactions**.
- Strict deletion safeguard: Prevents deletion of any item associated with existing stock movements to maintain ledger integrity.

### D. Stock Movements (Module 03)
- Transactional recording of stock **IN** (restock/receiving) and **OUT** (dispatch/requisition).
- Dropdown formatted as: `Item Code - Item Name (Current Quantity + Unit)`.
- Real-time stock status preview showing current balance and low-stock warning tags.
- Strict constraint enforcement: Stock OUT requests exceeding available quantity are rejected with an explicit error.
- Full transactional safety using `PDO::beginTransaction()`, `SELECT ... FOR UPDATE`, `PDO::commit()`, and `PDO::rollBack()`.
- Comprehensive **Stock History** table with multi-factor filters (Item, IN/OUT, Date Range) showing quantities under dedicated IN and OUT columns.

### E. Movement Reports & Printing (Module 04)
- Filter movements by Item, Category, Movement Type (All, IN, OUT), and Start/End Dates.
- **Three Dynamic Metrics:**
  1. Total Stock IN
  2. Total Stock OUT
  3. Net Movement (`Net Movement = Total Stock IN - Total Stock OUT`)
- Detailed tabular transaction ledger.
- Integrated **Print Functionality** (`window.print()`) styled via `@media print` to strip sidebars, headers, and navigation, generating an audit-ready physical report.

### F. User Management (Module 05)
- Create, update, role-assignment, password-reset, and deletion of system users.
- Unique username validation and mandatory minimum 6-character passwords.
- Self-deletion prevention: Prevents the logged-in administrator from deleting their own active account.
- Foreign key protection: Restricts deletion of users who have authored recorded stock movements.

### G. User Experience (Modules 06 & 07)
- Dedicated read-only dashboard for warehouse staff displaying high-level metrics and the latest 10 movements.
- Searchable item catalog with bin location tracking.
- Detailed item view presenting complete historical stock inflows and outflows.

---

## 4. Admin and User Permissions

| Functionality | Admin Role | User Role |
| :--- | :---: | :---: |
| Access Admin Dashboard | Yes | No |
| Access User Dashboard | Yes | Yes |
| View Items Catalog & Storage Locations | Yes | Yes |
| View Item Details & Movement History | Yes | Yes |
| Add / Edit / Delete Inventory Items | Yes | No |
| Record Stock IN / Stock OUT Transactions | Yes | No |
| View Stock Movement History Logs | Yes | Yes (Read-Only) |
| Generate & Print Movement Reports | Yes | No |
| Create, Edit, and Manage System Users | Yes | No |
| Reset User Passwords | Yes | No |

---

## 5. Project Folder Structure

```
Warehouse/
│
├── admin/
│   ├── dashboard.php          # Admin overview & low stock alerts (Module 01)
│   ├── items.php              # Full item management CRUD (Module 02)
│   ├── movements.php          # Transactional Stock IN / OUT & history (Module 03)
│   ├── reports.php            # Filterable reports & print preview (Module 04)
│   └── users.php              # User accounts & role management (Module 05)
│
├── api/
│   └── items.php              # Secure JSON item endpoint for dynamic lookup
│
├── assets/
│   ├── css/
│   │   └── style.css          # Core CSS3 styles, layout, and print rules
│   └── js/
│       └── app.js             # Vanilla JS for modals, alerts, print & UI
│
├── config/
│   ├── .htaccess              # Denies web access to sensitive config files
│   ├── config.php             # Core environment variables & base path resolver
│   └── database.php           # PDO connection instance with error handling
│
├── database/
│   ├── .htaccess              # Denies web access to SQL dumps
│   └── schema.sql             # Complete schema, indexes, FKs & seed data
│
├── includes/
│   ├── auth.php               # Session authentication, login/logout, RBAC
│   ├── footer.php             # Global HTML footer & JS loader
│   ├── functions.php          # Sanitization, CSRF, URL & helper routines
│   ├── header.php             # Dark green header & mobile drawer toggle
│   ├── init.php               # Bootstrap file initializing app & session
│   └── nav.php                # Dark green sidebar navigation
│
├── user/
│   ├── dashboard.php          # User read-only dashboard (Module 06)
│   ├── item-details.php       # Item specs & historical ledger (Module 07)
│   └── items.php              # Searchable item catalog (Module 07)
│
├── .htaccess                  # Directory browsing protection & security headers
├── index.php                  # Entry router directing to appropriate module
├── login.php                  # Authentication screen with demo helpers
├── logout.php                 # Session termination and cookie cleanup
└── README.md                  # Comprehensive technical documentation
```

---

## 6. Database Setup

The database schema is located at:
```
database/schema.sql
```

### Tables Included:
1. **`users`**: User records with credentials, roles (`admin`, `user`), and activation status.
2. **`items`**: Inventory catalog with bin locations, quantities, units, and reorder levels.
3. **`stock_movements`**: Immutable transaction log referencing `items.id` and `users.id` with `ON DELETE RESTRICT`.

### Importing Schema:
Using MySQL CLI:
```bash
mysql -u root -p < database/schema.sql
```
Or via phpMyAdmin:
1. Open phpMyAdmin (`http://localhost/phpmyadmin`).
2. Click **Import**.
3. Select `database/schema.sql` and click **Go**.

---

## 7. Default Login Credentials

| Role | Username | Password | Purpose |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Full administrative control |
| **User** | `demo` | `user123` | Warehouse staff read-only access |

*(On the login screen, click the demo account badges to auto-fill these credentials for quick testing.)*

---

## 8. XAMPP Installation Instructions

1. **Install XAMPP** (with PHP 8.0+ and MySQL).
2. Start the **Apache** and **MySQL** modules from the XAMPP Control Panel.
3. Place the project folder into your web root:
   ```
   C:\xampp\htdocs\warehouse_management_system
   ```
   *(or `C:\xampp\htdocs\Warehouse`)*
4. Open your browser and navigate to phpMyAdmin:
   ```
   http://localhost/phpmyadmin
   ```
5. Click the **Import** tab, browse for `database/schema.sql`, and execute the import.
6. Check database settings in `config/config.php`:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'warehouse_management');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
7. Open the application in your browser:
   ```
   http://localhost/warehouse_management_system/
   ```
   *(or `http://localhost/Warehouse/`)*
8. Sign in with `admin` / `admin123` or `demo` / `user123`.

---

## 9. cPanel Shared Hosting Deployment Instructions

1. **Upload Files:**
   - Package all project files into a `.zip` file (excluding local git folders).
   - In cPanel, navigate to **File Manager**.
   - Upload and extract the archive into `public_html/` (for root domain) or `public_html/warehouse/` (for subdirectory).
2. **Create MySQL Database & User:**
   - In cPanel, open **MySQL Databases**.
   - Create a new database (e.g., `yourcpanel_warehouse`).
   - Create a new MySQL user with a strong password.
   - Add the user to the database and grant **ALL PRIVILEGES**.
3. **Import Database Schema:**
   - Open **phpMyAdmin** in cPanel.
   - Select your newly created database.
   - Click the **Import** tab, upload `database/schema.sql`, and click **Go**.
4. **Configure Database & Path Settings:**
   - In cPanel File Manager, edit `config/config.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_PORT', '3306');
     define('DB_NAME', 'yourcpanel_warehouse');
     define('DB_USER', 'yourcpanel_dbuser');
     define('DB_PASS', 'YourStrongPasswordHere');
     ```
   - If installed in a subdirectory (e.g. `public_html/warehouse`), `BASE_PATH` is automatically resolved, or you can explicitly define:
     ```php
     define('BASE_PATH', '/warehouse');
     ```
     If hosted at root (`public_html/`), define:
     ```php
     define('BASE_PATH', '');
     ```
5. **Verify Security:**
   - Ensure `.htaccess` files in root, `config/`, and `database/` are active. Direct requests to `/config/config.php` or `/database/schema.sql` must return an HTTP 403 Forbidden.
   - Enable HTTPS in cPanel via Let's Encrypt / AutoSSL.

---

## 10. Database Configuration Instructions

All database connection variables are maintained in `config/config.php`:

```php
// Database Configuration
define('DB_HOST', '127.0.0.1');       // Database server hostname or IP
define('DB_PORT', '3306');            // Database port (default: 3306)
define('DB_NAME', 'warehouse_management'); // Database name
define('DB_USER', 'root');            // Database username
define('DB_PASS', '');                // Database password
define('DB_CHARSET', 'utf8mb4');      // High-compatibility UTF-8 encoding
```

The database connection class (`config/database.php`) automatically creates a singleton PDO connection with:
- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` (Strict error reporting)
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` (Associative arrays)
- `PDO::ATTR_EMULATE_PREPARES => false` (Native server-side prepared statements)

---

## 11. Security Information

1. **SQL Injection Prevention:** Every query with dynamic parameters uses PDO prepared statements with parameter binding.
2. **Password Hashing:** Passwords are never stored in plaintext; they are secured using PHP's standard bcrypt algorithm (`password_hash()`) and verified via `password_verify()`.
3. **Cross-Site Scripting (XSS) Defense:** All dynamic content rendered to the browser is escaped using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
4. **Cross-Site Request Forgery (CSRF):** Unique cryptographically secure session tokens (`random_bytes(32)`) validate all POST submissions.
5. **Session Fixation Prevention:** Session IDs are regenerated via `session_regenerate_id(true)` immediately upon successful authentication.
6. **Role-Based Access Control (RBAC):** Every administrative endpoint enforces `requireAdmin()`. Non-admin sessions attempting to access admin pages are blocked and redirected.
7. **Directory Listing & File Protection:** Apache `Options -Indexes` disables folder browsing. Dedicated `.htaccess` files block direct web access to `config/` and `database/`.
8. **Concurrency Control:** Stock OUT operations lock rows with `SELECT ... FOR UPDATE` inside an isolated transaction to prevent negative balance race conditions.
