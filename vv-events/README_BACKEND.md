# VV Events — PHP Backend & MySQL Database Guide

This project features a full PHP backend connected to a MySQL database, complete with REST APIs, dynamic calendar availability sync, and a luxury Admin Management Control Panel.

---

## 📁 Architecture Directory Structure

```
vv-events/
├── admin/                  # Admin Control Panel
│   ├── login.php           # Admin Login Page (Burgundy & Gold design)
│   ├── dashboard.php       # Bookings & Messages Management Dashboard
│   └── logout.php          # Session Logout Handler
├── api/                    # PHP REST API Layer
│   ├── db.php              # PDO Database Connection & Utility Functions
│   ├── book.php            # Event Booking Submission Endpoint
│   ├── contact.php         # Contact Inquiry Submission Endpoint
│   └── get_booked_dates.php# Dynamic Calendar Booked Dates Fetcher
├── database/
│   └── schema.sql          # Full MySQL Database Creation & Seed Data Script
├── js/
│   └── script.js           # Frontend AJAX Integration & Dynamic Calendar Sync
├── book.html               # Booking Page with Live Price Estimator
├── contact.html            # Contact Page with Inquiry Form
└── README_BACKEND.md       # Setup Instructions & Manual (This File)
```

---

## 🛠️ Prerequisites & Local Setup (XAMPP / WAMP / MAMP)

### Option 1: Using XAMPP / WAMP / LAMP (Recommended)

1. **Move Project Files**:
   - Copy or move the `vv-events` directory into your web server's document root:
     - **XAMPP**: `C:\xampp\htdocs\vv-events`
     - **WAMP**: `C:\wamp64\www\vv-events`
     - **MAMP**: `/Applications/MAMP/htdocs/vv-events`

2. **Start Apache & MySQL**:
   - Open your XAMPP/WAMP Control Panel and click **Start** next to **Apache** and **MySQL**.

3. **Import Database Schema**:
   - Open your browser and go to `http://localhost/phpmyadmin`.
   - Click on the **Import** tab at the top.
   - Choose the file: `vv-events/database/schema.sql`.
   - Click **Import** (or **Go**).
   - This automatically creates the `vv_events` database, `bookings`, `contact_messages`, and `admin_users` tables along with sample seed data.

4. **Verify Database Credentials**:
   - Open `api/db.php` and verify the MySQL connection constants match your environment:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'vv_events');
     define('DB_USER', 'root'); // default for XAMPP
     define('DB_PASS', '');     // default is empty for XAMPP
     ```

---

### Option 2: Using PHP Built-in Server & Local MySQL Server

1. Ensure PHP and MySQL are installed on your machine.
2. Import `database/schema.sql` using MySQL CLI or MySQL Workbench:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
3. Run the PHP built-in web server inside `vv-events`:
   ```bash
   php -S localhost:8000
   ```
4. Access the website at: `http://localhost:8000`.

---

## 👑 Admin Control Panel Access

To view and manage incoming bookings, customer details, status updates, and inquiry messages:

- **URL**: `http://localhost/vv-events/admin/login.php` (or `http://localhost:8000/admin/login.php`)
- **Default Username**: `admin`
- **Default Password**: `admin123`

### Features in Admin Dashboard:
- 📊 **Metrics Overview**: View Total Bookings, Pending Approvals, Confirmed Events, Unread Messages, and Total Confirmed Volume.
- 📅 **Bookings Management**: Filter by status, change status (`Pending` ➔ `Confirmed` ➔ `Cancelled`), view customer details, package choices, addons, and calculate totals.
- 💬 **Inquiry Messages**: Read customer messages, toggle status (`Unread` ➔ `Read` ➔ `Replied`), and delete inquiries.

---

## ⚡ API Endpoints Summary

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `api/book.php` | `POST` | Processes event booking forms, calculates package estimates, and inserts records into `bookings`. |
| `api/contact.php` | `POST` | Processes contact page inquiries and saves records into `contact_messages`. |
| `api/get_booked_dates.php` | `GET` | Fetches dates marked as confirmed or pending in MySQL to display live unavailable red dates on the calendar. |

---

## 🔒 Security Best Practices Implemented
- Prepared PDO statements (`PDO::prepare`) to protect against SQL Injection.
- Bcrypt password hashing (`password_hash` & `password_verify`) for admin authentication.
- Input sanitization (`trim`, `filter_var`, `htmlspecialchars`).
- Session validation for protected admin pages.
