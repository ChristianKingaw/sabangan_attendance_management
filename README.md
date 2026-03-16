# Attendance Management System

Attendance Management is a Laravel 12 web app that generates Daily Time Record (DTR) DOCX files from an uploaded XLS attendance report, then organizes those documents by department and employee for quick download. It is designed to run in a local or offline-friendly environment (for example, XAMPP).

**Table Of Contents**
1. Overview
2. Features
3. Tech Stack
4. Project Structure
5. Requirements
6. Installation And Setup
7. Configuration
8. Database Schema
9. Usage Guide
10. XLS Input Format
11. DOCX Template Requirements
12. Routes
13. Storage Layout
14. Troubleshooting
15. Security Notes

**Overview**
- Upload an XLS file that contains monthly attendance data.
- Parse attendance by employee and day.
- Auto-fill a DOCX template and generate one document per employee.
- Store generated files locally and archive attendance metadata.
- Browse attendance by department and employee, then download files individually or as a ZIP.

**Features**
- Admin login with session-based access control.
- XLS-only import with validation and error feedback.
- DOCX generation using a template stored in `template/DTR.docx`.
- Attendance archive view grouped by department and employee.
- One-click ZIP download of all attendance documents for an employee.
- Local storage with explicit file paths and database records.

**Tech Stack**
- Laravel 12 (PHP 8.2+)
- MySQL or MariaDB
- Bootstrap (local assets under `public/vendor/bootstrap`)
- Vite toolchain for front-end assets
- PHPSpreadsheet for XLS parsing
- ZipArchive for DOCX and ZIP creation

**Project Structure**
- `app/Http/Controllers` contains the main app logic.
- `app/Http/Middleware/AdminAuth.php` protects admin-only routes.
- `resources/views` contains Blade templates for login, generate, and attendance pages.
- `template/DTR.docx` is the DOCX template used to generate attendance files.
- `storage/app/attendance` holds generated DOCX files.
- `database/migrations` includes attendance-related migrations.

**Requirements**
- PHP 8.2+
- Composer
- Node.js and npm (for Vite builds)
- MySQL or MariaDB
- PHP extensions: `zip` (required for DOCX and ZIP generation), `pdo_mysql` (database access), and standard Laravel extensions such as `mbstring`, `openssl`, `xml`, `curl`, `fileinfo`

**Installation And Setup**
1. Install PHP dependencies: `composer install`
2. Install front-end dependencies: `npm install`
3. Create environment file (Windows): `copy .env.example .env`
4. Generate app key: `php artisan key:generate`
5. Configure database connection in `.env`.
6. Run migrations: `php artisan migrate`
7. Start the app: `php artisan serve`

You can also run the bundled composer scripts:
- `composer run setup` runs install, environment setup, migrations, and a production asset build.
- `composer run dev` runs the PHP server, queue listener, and Vite dev server concurrently.

**Configuration**
Set the following in `.env`:
- `APP_URL` to match your local domain.
- `DB_*` values to point to your MySQL database.
- `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION` depending on your environment.

Note: The repository does not include migrations for `sessions`, `cache`, or `jobs` tables. If you keep the `.env` defaults that use database drivers, generate those tables with:
- `php artisan session:table`
- `php artisan cache:table`
- `php artisan queue:table`
- `php artisan migrate`

**Database Schema**
This app expects three primary tables. Two are legacy and must already exist, and one is created by migration.

Admin table (legacy, required):
- `admin` with columns `id`, `username`, `password` (bcrypt hash).

File table (legacy, required):
- `file` with columns `id`, `adminID`, `date` (int in `YYYYMMDD` format), `filename`, `path`.

Attendance records (created by migration):
- `attendance_records` with columns `id`, `admin_id`, `employee_id`, `employee_name`, `department`, `period_raw`, `period_date`, `attendance` (JSON), `document_path`, `created_at`, `updated_at`.

If the `admin` and `file` tables do not exist, create them before running the migrations in this repo.

**Usage Guide**
- Admin login: visit `/login` and enter a valid username and password from the `admin` table.
- Generate DOCX from XLS: go to `/`, upload an `.xls` file, and the app creates one DOCX per employee in `storage/app/attendance`. The `file` and `attendance_records` tables are updated or inserted.
- Browse attendance: go to `/attendance`, select a department and employee, then click a period to open the DOCX file.
- Download ZIP: use the "Download All" button to bundle all DOCX files for the selected employee and download them as a ZIP.
- Delete a document: call `DELETE /documents/{id}` to remove the file from disk and delete the `file` record.

**XLS Input Format**
The XLS parser looks for specific patterns. Ensure your report matches these rules:
- The report must contain a row with the label `Att. Time`. The value next to it is stored as the period label.
- The day row is detected when a row contains day numbers (1-31) and includes day 1, with at least 10 day values on the row.
- Each employee section must include a row with the label `ID:` and `Name:`. `Dept.` or `Dept:` is optional.
- The attendance data row should appear after the employee header row and should contain time values under the day columns.

Attendance cell parsing rules:
- Times are detected using the `HH:MM` pattern (for example, `08:00 12:00 13:00 17:00`).
- The first four times are mapped to AM In, AM Out, PM In, PM Out in that order.
- Special codes supported: `25` or `LEAVE` -> `Leave`, `26` or `OUT` -> `Out`, `HOLIDAY` or `HOL` -> `Holiday`, `OFF` -> `Off`.
- If a cell does not match any time or special code, it is stored as a note and passed through as-is.

**DOCX Template Requirements**
The file `template/DTR.docx` is mandatory and must be a valid DOCX.

Placeholders supported in the template:
- `{employeeName}` or `{name}`
- `{month}`
- `{year}`

Table requirements:
- The template should contain tables where the first column is the day number.
- If a row has 5 columns, the app fills AM In, AM Out, PM In, PM Out.
- If a row has 3 columns, the app fills Arrival and Departure.

If the template is missing, empty, or invalid, the upload will fail with a clear error.

**Routes**
Public:
- `GET /login` admin login screen
- `POST /login` authenticate and create admin session
- `POST /logout` end admin session

Protected by `admin.auth` middleware:
- `GET /` generate page
- `POST /documents/upload` upload XLS and generate DOCX files
- `GET /documents/{id}` open a generated DOCX from the `file` table
- `DELETE /documents/{id}` delete a generated DOCX
- `GET /attendance` attendance archive view
- `GET /attendance/documents/{id}` open DOCX by attendance record id
- `GET /attendance/employee-zip` download ZIP for an employee

**Storage Layout**
- Uploaded XLS files are processed directly and are not stored by default.
- Generated DOCX files are stored at `storage/app/attendance`.
- ZIP files are generated in `storage/app` and deleted after download.

**Troubleshooting**
- "Database table \"file\" does not exist": create the legacy `file` table before uploading.
- "Template file template/DTR.docx was not found": place your DOCX template in `template/DTR.docx`.
- "PHP Zip extension is required": enable the `zip` extension in your PHP installation.
- "No employee records found in the XLS file": verify the XLS format matches the expected labels and day layout.
- Attendance page is empty: run the migration for `attendance_records` and ensure uploads are completed.

**Security Notes**
- Authentication is simple and based on a single `admin` table.
- Passwords must be stored as bcrypt hashes.
- The app assumes trusted internal usage. If exposed publicly, add rate limiting, strong password policies, and HTTPS.
