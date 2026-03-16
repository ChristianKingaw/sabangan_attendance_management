# Attendance Management System

> Generate Daily Time Record (DTR) DOCX files from attendance XLS reports with a Laravel 12 web app.

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel) ![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php) ![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

Attendance Management is a Laravel 12 web app designed to streamline DTR document generation and management. Upload an attendance XLS file, and the system automatically generates personalized DOCX files for each employee, organized by department for easy browsing and downloading. Built for local or offline-friendly environments like XAMPP.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [Database Schema](#database-schema)
- [Usage Guide](#usage-guide)
- [XLS Input Format](#xls-input-format)
- [DOCX Template Requirements](#docx-template-requirements)
- [API Routes](#api-routes)
- [Storage Layout](#storage-layout)
- [Troubleshooting](#troubleshooting)
- [Security Notes](#security-notes)

---

## Overview

- Upload an XLS file containing monthly attendance data
- Parse attendance records by employee and day
- Auto-fill a DOCX template to generate one document per employee
- Store generated files locally with archived attendance metadata
- Browse attendance records by department and employee, then download files individually or as a ZIP

## Features

✨ **Core Functionality**

- Session-based admin login with access control
- XLS-only import with validation and error feedback
- DOCX generation using a customizable template (`template/DTR.docx`)
- Attendance archive view grouped by department and employee
- One-click ZIP download for all attendance documents per employee
- Local storage with explicit file paths and database records

## Tech Stack

| Technology | Purpose |
|---|---|
| **Laravel 12** | PHP web framework |
| **PHP 8.2+** | Backend runtime |
| **MySQL / MariaDB** | Database |
| **Bootstrap** | UI framework (local assets) |
| **Vite** | Front-end toolchain |
| **PHPSpreadsheet** | XLS parsing |
| **ZipArchive** | DOCX & ZIP creation |

## Requirements

### System

- **PHP** 8.2 or higher
- **Node.js** and npm (for Vite builds)
- **Composer** (PHP dependency manager)
- **MySQL** or **MariaDB**

### PHP Extensions

Required extensions:
- `zip` - for DOCX and ZIP generation
- `pdo_mysql` - for database access
- `mbstring`, `openssl`, `xml`, `curl`, `fileinfo` - Laravel standard extensions

---

## Quick Start

### 1. Installation

```bash
# Install PHP dependencies
composer install

# Install front-end dependencies
npm install

# Create environment file (Windows)
copy .env.example .env

# Generate app key
php artisan key:generate
```

### 2. Database Setup

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=attendance_db
DB_USERNAME=root
DB_PASSWORD=
```

Then run migrations:

```bash
php artisan migrate
```

### 3. Run the Application

**Option A: Manual Start**
```bash
php artisan serve
```

**Option B: Composer Scripts**
```bash
# Full setup with migrations and asset build
composer run setup

# Development mode (PHP server + Vite dev server)
composer run dev
```

---

## Project Structure

```
├── app/Http/Controllers/          # Main application logic
├── app/Http/Middleware/           # AdminAuth middleware for protected routes
├── resources/views/               # Blade templates (login, generate, attendance)
├── template/                      # DTR.docx template (required)
├── storage/app/attendance/        # Generated DOCX files
├── database/migrations/           # Database migrations
└── public/vendor/bootstrap/       # Local Bootstrap assets
```

## Configuration

### Environment Variables

Set these in `.env`:

| Variable | Purpose |
|---|---|
| `APP_URL` | Local domain URL |
| `DB_*` | Database connection details |
| `SESSION_DRIVER` | Session storage (`database`, `file`, `cookie`) |
| `CACHE_STORE` | Cache backend (`file`, `database`, `array`) |
| `QUEUE_CONNECTION` | Queue driver (`sync`, `database`, `redis`) |

### Database Tables Setup

If using database drivers for sessions, cache, or jobs:

```bash
php artisan session:table
php artisan cache:table
php artisan queue:table
php artisan migrate
```

---

## Database Schema

This app uses **three primary tables**. Two are legacy (must exist before setup), one is created by migration.

### Admin Table (Legacy, Required)

```sql
CREATE TABLE admin (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL
);
```

### File Table (Legacy, Required)

```sql
CREATE TABLE file (
  id INT PRIMARY KEY AUTO_INCREMENT,
  adminID INT NOT NULL,
  date INT NOT NULL,              -- Format: YYYYMMDD
  filename VARCHAR(255) NOT NULL,
  path VARCHAR(255) NOT NULL
);
```

### Attendance Records (Created by Migration)

```sql
CREATE TABLE attendance_records (
  id INT PRIMARY KEY AUTO_INCREMENT,
  admin_id INT NOT NULL,
  employee_id VARCHAR(255),
  employee_name VARCHAR(255) NOT NULL,
  department VARCHAR(255),
  period_raw VARCHAR(255),
  period_date DATE,
  attendance JSON,                -- Attendance data
  document_path VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

> **Note:** Create the `admin` and `file` tables before running migrations if they don't exist.

---

## Usage Guide

### Admin Login
Visit `/login` and enter credentials from the `admin` table.

### Generate DOCX from XLS
1. Go to `/` (home page)
2. Upload a `.xls` file
3. The app generates one DOCX per employee in `storage/app/attendance`
4. Database tables are automatically updated

### Browse Attendance
1. Go to `/attendance`
2. Select a department and employee
3. Click a period to open the DOCX file

### Download All Documents
Use the **"Download All"** button to bundle all DOCX files for an employee as a ZIP file.

### Delete a Document
```http
DELETE /documents/{id}
```
This removes the file from disk and deletes the `file` record.

---

## XLS Input Format

The XLS parser looks for specific patterns. Your report must follow these rules:

### Report Structure

- Must contain a row with label **`Att. Time`** (the value becomes the period label)
- Day row contains day numbers (1-31) with day 1, plus at least 10 day values
- Each employee section includes:
  - `ID:` and `Name:` labels
  - `Dept.` or `Dept:` label (optional)
- Attendance data row appears after employee header with time values under day columns

### Attendance Cell Parsing

**Time Format:** Detected using `HH:MM` pattern (e.g., `08:00 12:00 13:00 17:00`)
- First time → AM In
- Second time → AM Out
- Third time → PM In
- Fourth time → PM Out

**Special Codes:**
| Code | Mapped To |
|---|---|
| `25`, `LEAVE` | Leave |
| `26`, `OUT` | Out |
| `HOLIDAY`, `HOL` | Holiday |
| `OFF` | Off |

Other values are stored as notes and passed through as-is.

---

## DOCX Template Requirements

The file **`template/DTR.docx`** is **mandatory** and must be a valid DOCX file.

### Supported Placeholders

Replace these in your template:
- `{employeeName}` or `{name}` - Employee name
- `{month}` - Month number
- `{year}` - Year number

### Table Format

Tables in the template should have:
- First column = day number
- **5 columns** → AM In, AM Out, PM In, PM Out
- **3 columns** → Arrival, Departure

> ⚠️ If the template is missing, empty, or invalid, uploads will fail with a clear error message.

---

## API Routes

### Public Routes

```http
GET    /login                    # Admin login screen
POST   /login                    # Authenticate & create session
POST   /logout                   # End session
```

### Protected Routes (Requires `admin.auth` Middleware)

```http
GET    /                         # Generate page
POST   /documents/upload         # Upload XLS & generate DOCX files
GET    /documents/{id}           # Open generated DOCX
DELETE /documents/{id}           # Delete generated DOCX
GET    /attendance               # Attendance archive view
GET    /attendance/documents/{id}# Open DOCX by attendance record
GET    /attendance/employee-zip  # Download employee's ZIP
```

---

## Storage Layout

| Path | Purpose |
|---|---|
| `storage/app/attendance/` | Generated DOCX files (persistent) |
| `storage/app/` | Temporary ZIP files (deleted after download) |
| Uploaded files | Processed directly (not stored) |

---

## Troubleshooting

| Error | Solution |
|---|---|
| "Database table \"file\" does not exist" | Create the legacy `file` table before uploading |
| "Template file template/DTR.docx was not found" | Place `DTR.docx` in the `template/` directory |
| "PHP Zip extension is required" | Enable `zip` extension in `php.ini` |
| "No employee records found in the XLS file" | Verify XLS format matches expected labels & layout |
| Attendance page is empty | Run migration & ensure uploads completed |

---

## Security Notes

⚠️ **Important:** This app is designed for **trusted internal usage** in controlled environments.

- Authentication uses a single `admin` table with bcrypt password hashes
- No built-in rate limiting or password policies
- Suitable for local/offline environments (e.g., XAMPP)

**For public deployment, add:**
- Rate limiting on login endpoints
- Strong password policies
- HTTPS enforcement
- Additional access controls
