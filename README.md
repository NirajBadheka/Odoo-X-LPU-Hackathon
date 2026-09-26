# StockSense Pro — Enterprise Inventory Management System (IMS)

> **Built for Odoo Hackathon (Virtual Round)**  
> **Theme:** Odoo Enterprise Violet (`#714B67`) & Mint Emerald (`#00A09D`)  
> **Database:** `pro_stocksense`  
> **Stack:** PHP 8+ (Core PHP), MySQL / MySQLi (Prepared Statements), HTML5, CSS3, Bootstrap 5.3, Vanilla JavaScript, SweetAlert2, Chart.js

---

## 🌟 Hackathon Evaluation Highlights

StockSense Pro is built directly against the official Odoo Hackathon Problem Statement specifications:

1. **Target Users & Role-Based Access Control (RBAC)**:
   - **Inventory Manager (`manager`)**: Full operational oversight, approvals, warehouse/location configuration, product reordering rules, and move ledger audits.
   - **Warehouse Staff (`staff`)**: Executes receipts, physical item picking and packing for deliveries, internal transfers, and physical count adjustments.
2. **Double-Entry Stock Movement Ledger (`stock_moves`)**:
   - Mirrors Odoo’s authentic immutable ledger. Every incoming receipt, outgoing customer shipment, internal location transfer, and stock variance write-off is permanently recorded in the double-entry journal.
3. **Document Workflow State Machines**:
   - Receipts: `Draft` ➔ `Waiting` ➔ `Ready` ➔ `Done` (Stock automatically increases).
   - Delivery Orders: `Draft` ➔ `Waiting` (Pick) ➔ `Ready` (Pack) ➔ `Done` (Stock automatically decreases).
   - Internal Transfers: Source Location ➔ Destination Location (Company stock balance unchanged; location breakdown updated).
   - Stock Adjustments: Theoretical recorded count vs. actual physical count (Automated variance computation and write-off reasoning).
4. **Public Landing Page & Common Dashboard**:
   - Live KPI overview (Total Products, Low Stock alerts, Pending Receipts, Pending Deliveries, Scheduled Transfers).
   - Interactive 4-step supply chain flow visualizer.
   - 1-Click Demo Login credentials.
5. **Security & Performance**:
   - 100% Prepared Statements (`mysqli::prepare`) against SQL injection.
   - CSRF token validation on all state-changing forms.
   - Secure Bcrypt password hashing (`password_hash`).
   - Clean, lightweight Vanilla JavaScript (zero bulky frontend build dependencies).

---

## 🔑 Demo Credentials (1-Click Auto-Fill Ready)

| Role | Email | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Inventory Manager** | `manager@stocksense.com` | `manager123` | Full Oversight, Approvals, Warehouses, Reorder Rules |
| **Warehouse Staff** | `staff@stocksense.com` | `staff123` | Picking, Packing, Shelving, Counting, Internal Transfers |

*Tip: On the login page, you can click either the **"Demo Manager"** or **"Demo Staff"** badge to automatically populate the credentials.*

---

## 🚀 1-Minute Setup Instructions on XAMPP

### Option A: Using the Automated 1-Click Web Installer (Recommended)
1. Copy the `stocksense_pro` folder into your XAMPP web root:
   ```
   C:\xampp\htdocs\stocksense_pro
   ```
2. Start **Apache** and **MySQL** in your XAMPP Control Panel.
3. Open your browser and navigate to:
   ```
   http://localhost/stocksense_pro/setup.php
   ```
4. Click **"Create Database & Seed Data"**.  
   *The installer will automatically create `pro_stocksense`, construct all 13 relational tables, and seed realistic demo products and movements.*
5. Click **"Launch StockSense Pro"** and sign in!

### Option B: Manual phpMyAdmin Import
1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a new database named **`pro_stocksense`** (Collation: `utf8mb4_unicode_ci`).
3. Import the SQL file located at:
   ```
   stocksense_pro/database/pro_stocksense.sql
   ```
4. Access `http://localhost/stocksense_pro/index.php`.

---

## 📁 Project Structure

```
stocksense_pro/
│
├── config/
│   ├── database.php            # MySQLi connection, prepared statements & ledger functions
│   ├── constants.php           # App configuration, base URL resolver, status badges
│   └── auth_check.php          # Session guards, RBAC helpers & CSRF verification
│
├── database/
│   └── pro_stocksense.sql      # Complete DDL relational schema & rich seed data
│
├── assets/
│   ├── css/
│   │   └── style.css           # Theme 1: Odoo Enterprise Violet & Mint CSS design system
│   └── js/
│       └── app.js              # Vanilla JS for preloader, dynamic table search & filters
│
├── includes/
│   ├── header.php              # Global HTML head, topbar, user dropdown & preloader
│   ├── sidebar.php             # Role-aware left navigation sidebar
│   ├── footer.php              # System version & status footer
│   └── scripts.php             # Bootstrap 5.3, SweetAlert2, and Chart.js bundles
│
├── auth/
│   ├── login.php               # Login with 1-click Demo credentials auto-filler
│   ├── register.php            # User registration with role selection
│   ├── forgot_password.php     # OTP password reset step 1 (Request OTP)
│   ├── verify_otp.php          # OTP password reset step 2 (Enter 6-digit code)
│   ├── reset_password.php      # OTP password reset step 3 (Set new password)
│   └── logout.php              # Safe session destruction
│
├── operations/
│   ├── receipts.php            # Incoming stock list & status filters
│   ├── receipt_create.php      # Create vendor receipt with multi-item rows
│   ├── receipt_view.php        # Inspect items & 1-click validate to increase stock
│   ├── deliveries.php          # Outgoing stock customer shipments list
│   ├── delivery_create.php     # New delivery order
│   ├── delivery_view.php       # Odoo 3-step workflow: Pick -> Pack -> Validate
│   ├── transfers.php           # Internal transfers (Main Store -> Production)
│   ├── transfer_create.php     # Schedule movement between racks
│   ├── adjustments.php         # Physical count vs. recorded stock reconciliation
│   ├── adjustment_create.php   # Real-time variance calculation & damage log
│   └── move_history.php        # Immutable Double-Entry Stock Ledger audit journal
│
├── products/
│   ├── index.php               # Products catalog with low-stock warnings & SKU search
│   ├── create.php              # Create product with optional initial stock ledger credit
│   ├── edit.php                # Edit product & reordering rules
│   ├── categories.php          # Product categories management
│   └── stock_by_location.php   # Real-time stock matrix per warehouse and rack
│
├── settings/
│   └── warehouses.php          # Manage physical warehouses & individual storage bays
│
├── profile/
│   └── index.php               # User profile info & password change
│
├── index.php                   # Public landing page with live snapshot dashboard
├── dashboard.php               # Internal role-based dashboard with Chart.js & filters
├── setup.php                   # 1-Click database auto-installer and seeder
└── README.md                   # System documentation & hackathon guide
```

---

## 🏆 Summary of Features Implemented
- [x] Odoo Enterprise Violet & Mint Design Theme
- [x] Full Relational Database Schema (`pro_stocksense`) with Foreign Keys
- [x] 1-Click Database Setup script (`setup.php`)
- [x] Public Landing Page with Live Operational Snapshot & KPIs
- [x] 1-Click Auto Demo Credential Fillers for Manager and Staff
- [x] Role-Based Access Control (RBAC)
- [x] OTP-based Password Reset flow (Request -> Verify 6-digit OTP -> Set New Password)
- [x] Product Management with Reordering Min/Max Safety Rules
- [x] Multi-Location Stock Distribution Matrix (`stock_by_location.php`)
- [x] Product Categories Management
- [x] Receipts Workflow with Automatic Stock Increase & Ledger Entry
- [x] Delivery Orders Workflow with Pick -> Pack -> Validate & Stock Decrease
- [x] Internal Transfers (Location A -> Location B, total stock unchanged)
- [x] Stock Adjustments with Automatic Variance Calculation & Damage Write-Off
- [x] Immutable Double-Entry Stock Ledger (`move_history.php`)
- [x] Interactive Chart.js Visualizations (Stock Valuation by Category)
- [x] Dynamic Real-Time Filters (Document Type, Status, Warehouse, SKU Search)
- [x] Preloader & Smooth Micro-Interactions
- [x] SweetAlert2 Notifications & Confirmations
