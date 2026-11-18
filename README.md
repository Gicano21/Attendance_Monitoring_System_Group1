# CampusPulse Attendance Management System

A CodeIgniter 4 application for role-based student and instructor attendance, using scheduled ESP32 attendance submissions and the existing campus database.

## Project progress

| Area | Status |
| --- | --- |
| Student and instructor landing/login pages | Implemented |
| Admin login at `/myadmin/` (not linked from the public landing page) | Implemented |
| Role-specific attendance dashboards and password changes | Implemented |
| Role-specific view organization under `app/Views/pages/{admin,instructor,student}` | Implemented; shared shell and forms use layouts/partials |
| Dashboard view-data contract across Admin, Instructor, and Student pages | Implemented and covered by render regression tests |
| Admin CRUD for programs, courses, students, and instructors | Implemented |
| Instructor class roster add/edit/remove | Implemented; removal un-enrols from the instructor's assigned classes |
| Weekly instructor attendance windows for an assigned program/course and weekday | Implemented; existing one-time schedules are preserved |
| JSON attendance endpoint for ESP32 ID/Role submissions, protected by a device key and schedule/account checks | Implemented; hardware integration not yet field-tested |
| Scheduled automatic close of expired attendance windows | Implemented as a CLI command; configure an OS scheduler |
| Browser QR camera scanner | Not used; the ESP32 device sends attendance data directly |
| Admin UI for faculty/course/program assignments | Implemented on the Instructors page using the existing assignment tables |

**Progress update requirement:** Every AI agent or contributor who changes this project must update this table and the related setup/feature notes in this README before finishing. Record what changed, what was verified, and any remaining setup or limitations. Do not mark a feature complete unless it has been run or tested.

**Latest implementation note:** Instructors select a recurring weekday (Monday-Sunday) instead of a class date. New weekly schedules are matched by weekday for ESP32 check-ins and auto-close; existing date-specific schedules remain valid on their original date. Attendance submissions use the JSON endpoint and require `ATTENDANCE_DEVICE_API_KEY`; browser camera scanning and participant-session requirements are removed. Views are separated by role under `app/Views/pages/`, with a shared layout and reusable components. Validation passed: 15 PHPUnit tests (89 assertions), PHP syntax checks, and route listing. Physical ESP32/hardware integration has not yet been field-tested.

## Application structure

- `app/Controllers/` contains request handling and role/business logic.
- `app/Views/pages/admin/`, `instructor/`, and `student/` contain each role's dashboard views; `pages/home.php` and `pages/login.php` are shared public pages.
- `app/Views/layouts/` contains the common page shell. `app/Views/partials/` contains shared navigation, footer, authentication, attendance, and resource-management components.
- `app/Database/Migrations/` and `app/Database/Seeds/` contain schema and starter data; `app/Commands/` contains the scheduled attendance closeout command.

## Requirements and setup

1. Use PHP 8.2+ with the CodeIgniter-required extensions and a configured MySQL-compatible database.
2. Copy the project environment template to `.env` if needed and set the application URL and database credentials there. Do not commit `.env` or credentials.
3. From the project root, install dependencies if needed:

   ```sh
   composer install
   ```

4. Apply migrations:

   ```sh
   php spark migrate
   ```

   The attendance workflow migration widens the existing password fields, hashes any legacy plaintext passwords, and creates `attendance_schedules` and `attendance_records`. The following migration adds `day_of_week` for recurring weekly schedules without changing legacy one-time schedule rows.
5. To load the sample records and initial admin, set `ATTENDANCE_ADMIN_USERNAME`, `ATTENDANCE_ADMIN_EMAIL`, `ATTENDANCE_ADMIN_PASSWORD`, and `ATTENDANCE_INITIAL_PASSWORD` in the local environment, then run `php spark db:seed InitialData` once. The seed includes example programs, courses, instructors, students, instructor levels, and enrollment reference data. Do not use sample accounts in production.
6. Add program and course master records, instructor accounts, and instructor levels. Use the assignment panel on the Instructors page to map each instructor to the program/course they teach. Student enrollment requires the existing reference records in `year`, `school_year`, `semesters`, and `sections`.
7. Set `appTimezone` in `app/Config/App.php` to the campus timezone so schedule checks and scans use campus local time. Point the web server document root to `public/`, then run locally with:

   ```sh
   php spark serve
   ```

The database connection defaults in `app/Config/Database.php` are intentionally blank; set credentials in the local environment rather than source control.

Set `ATTENDANCE_DEVICE_API_KEY` in the server's environment or `.env` to a random secret of at least 32 characters. Generate one with `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Keep it out of source control and configure the same value in the ESP32 firmware as an `X-Device-Key` request header. Use HTTPS between the ESP32 and server.

## Role access

- Public landing page: `/`
- Student sign-in: `/student`
- Instructor sign-in: `/instructor`
- Admin sign-in: `/myadmin/` (admin pages are under `/myadmin/...`; there is no public admin link)

Students and instructors sign in with their numeric account ID and password. Admins sign in with their username and password. Accounts can change passwords after login. On successful sign-in, a legacy unhashed password is upgraded to a password hash.

The initial data seeder requires `ATTENDANCE_ADMIN_USERNAME`, `ATTENDANCE_ADMIN_EMAIL`, `ATTENDANCE_ADMIN_PASSWORD`, and `ATTENDANCE_INITIAL_PASSWORD` to be set in the local environment. Use passwords of at least 8 characters and do not add them to source control. The shared initial password is used only when creating seeded demo student/instructor accounts; change those passwords after setup.

## ESP32 attendance API

The ESP32 sends an HTTP `POST` to `/api/Scan_QR_Code` with JSON. Uppercase keys and singular role values are accepted:

```json
{"ID": 123, "Role": "Student"}
```

For an instructor:

```json
{"ID": 12, "Role": "Instructor"}
```

Include the configured device secret in the `X-Device-Key` header. The ESP32 does not need a browser login or session cookie. Example request:

```sh
curl -X POST "https://your-host/api/Scan_QR_Code" \
  -H "Content-Type: application/json" \
  -H "X-Device-Key: YOUR_CONFIGURED_DEVICE_KEY" \
  -d '{"ID":123,"Role":"Student"}'
```

The endpoint verifies the device key, validates the participant and role against the database, and requires a matching attendance schedule to be active for the current weekday and time (or on its original date for a legacy one-time schedule). It then toggles `in_campus` and records an `IN` or `OUT` event. Student check-ins also populate `student_time_ins`; instructor check-ins populate `instructor_time_in`. Instructors can check in once per day. A successful response includes `ok`, `role`, `id`, `status`, and `scanned_at`. Invalid JSON/fields, bad credentials, missing participants, inactive schedules, and other rejected scans return a JSON message with an appropriate HTTP error status.

The ID/Role body is not itself authentication. Keep the device key private, use HTTPS, limit network access to the ESP32/device network where possible, and rotate the key if a device is lost or compromised. Do not send the key in the URL or JSON body.

## Automatic close of expired schedules

The command clears `in_campus` and records an `OUT` event when a participant's last scan is an `IN` for a weekly schedule whose weekday and end time have passed, or for a legacy one-time schedule whose date/end time has passed:

```sh
php spark attendance:auto-close
```

Configure Windows Task Scheduler (or cron on Linux) to run this command once per minute. A web request does not run the background check automatically; the OS scheduler is required.

## Validation

Run the PHPUnit suite from the project root:

```sh
composer test
```

The test database uses SQLite; enable the `sqlite3` and `pdo_sqlite` PHP extensions. On Windows where the extensions are installed but not enabled in `php.ini`, the suite can be run with:

```powershell
php -d extension=php_sqlite3.dll -d extension=php_pdo_sqlite.dll vendor\bin\phpunit --no-progress --no-coverage
```

Also run `php spark routes` to inspect registered routes and `php spark migrate` against a disposable database before deploying schema changes.

### Existing database without migration history

The original project may have its legacy tables created manually or by a different deployment process. Running `php spark migrate` against such a database with no matching CodeIgniter migration history makes CodeIgniter replay the initial `CREATE TABLE` migrations; it will fail on existing tables such as `admin_accounts`. Do not keep retrying and do not drop or rename application tables to get past the error.

For an existing installation:

1. Take a verified full database backup and restore it to a disposable clone. Keep production untouched during reconciliation.
2. Inspect the cloned schema and compare it with every `up()` method in `app/Database/Migrations` through `2026-11-11-064420_ProgramsAssignment.php`. Confirm all tables, columns, and later alterations from those legacy migrations are already present. A table-name-only check is not sufficient.
3. If, and only if, the clone exactly contains the results of all those legacy migrations, baseline those **legacy versions only** in the configured CodeIgniter `migrations` history table. Use each filename's digits-only version, its class name, the configured database group (normally `default`), and namespace `App\Database\Migrations`. Keep the attendance workflow migration `2026-11-19-000001_AttendanceWorkflow` and the weekday migration `2026-11-19-000002_AddAttendanceScheduleDayOfWeek` unrecorded so CodeIgniter applies them normally.
4. Run `php spark migrate` on the clone. Confirm the new migration succeeds, existing records remain intact, and legacy passwords authenticate after the hash upgrade. Only then repeat the reviewed baseline and migration steps against production during a planned backup window.
5. If any legacy migration effect is missing or uncertain, stop and write/review a corrective migration for that schema difference. Do not mark the missing migration as applied.

If the database already has a `migrations` table, preserve its history and reconcile only after reviewing its rows. Never clear the table, invent batches, or mark versions applied based on table names alone. For new installations, use the normal `php spark migrate` command on an empty database; no baseline step is needed.
