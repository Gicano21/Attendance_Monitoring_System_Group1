# CampusPulse Attendance Management System

A CodeIgniter 4 application for role-based student and instructor attendance, with scheduled attendance windows and the existing campus database.

## Project progress

| Area | Status |
| --- | --- |
| Student and instructor landing/login pages | Implemented |
| Admin login at `/myadmin/` (not linked from the public landing page) | Implemented |
| Role-specific attendance pages and password changes | Implemented |
| Destination-specific controllers and views | Implemented; shared shell and reusable forms use layouts/partials |
| Model-backed validation for programs, courses, accounts, assignments, schedules, and enrolments | Implemented |
| QR scanner submission endpoint | Removed; scanner integration is left for the project owner |
| Admin CRUD for programs, courses, students, and instructors | Implemented |
| Instructor class roster add/edit/remove | Implemented; removal un-enrols from the instructor's assigned classes |
| Weekly instructor attendance windows for an assigned program/course and weekday | Implemented; existing one-time schedules are preserved |
| Scheduled automatic close of expired attendance windows | Implemented as a CLI command; configure an OS scheduler |
| Browser QR camera scanner | Not included |
| Admin UI for faculty/course/program assignments | Implemented on the Instructors page using the existing assignment tables |
| Shield authentication and role-based authorization | Implemented for admin, instructor, and student accounts |
| Wasmer database environment configuration | Implemented; deployment must provide the `DB_*` connection variables |

**Progress update requirement:** Every AI agent or contributor who changes this project must update this table and the related setup/feature notes in this README before finishing. Record what changed, what was verified, and any remaining setup or limitations. Do not mark a feature complete unless it has been run or tested.

**Latest implementation note:** Instructors select a recurring weekday (Monday-Sunday) instead of a class date. Existing date-specific schedules remain valid on their original date. Every navigation destination has its own controller and view, while shared layout and reusable components avoid duplicating presentation. Entity validation rules live in models. The QR scan API route and its device-key setup have been removed; implement the scanner submission flow separately. Wasmer database settings are read from `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, and `DB_PASSWORD` rather than stored in source. Verification passed: 6 focused PHPUnit tests (54 assertions), PHP syntax checks, route listing, login-page smoke checks, and confirmation that the removed endpoint returns 404. Database connectivity must be confirmed in the Wasmer deployment after rotating the exposed password.

**Shield authentication update:** CodeIgniter Shield now authenticates all three portal roles, assigns role groups, protects role-specific routes, rate-limits login pages, and uses session-based CSRF. Existing account IDs and login pages are retained. The Shield account migration imports usable existing password hashes and converts legacy plaintext passwords; malformed or truncated legacy hashes are logged and must be reset. Verification: PHP syntax checks and route listing passed; the portal view/model tests passed (5 tests, 52 assertions). The role-route feature test could not run because the configured PHP lacks `sqlite3`.

## Application structure

- `app/Controllers/` contains destination-specific controllers and shared portal workflows.
- `app/Models/` contains data models and their validation rules.
- `app/Views/pages/admin/`, `instructor/`, and `student/` contain destination-specific pages; each sign-in role has its own view.
- `app/Views/layouts/` contains the common page shell. `app/Views/partials/` contains shared navigation, footer, authentication, attendance, and resource-management components.
- `app/Database/Migrations/` and `app/Database/Seeds/` contain schema and starter data; `app/Commands/` contains the scheduled attendance closeout command.

## Requirements and setup

1. Use PHP 8.2+ with the CodeIgniter-required extensions and a configured MySQL-compatible database.
2. For local development, copy the project environment template to `.env` if needed and set the application URL and database credentials there. Do not commit `.env` or credentials. On Wasmer, configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, and `DB_PASSWORD` as application environment variables; Wasmer provides these for its attached database.
3. From the project root, install dependencies if needed:

   ```sh
   composer install
   ```

4. Apply application and Shield migrations:

   ```sh
   php spark migrate --all
   ```

   Shield creates its authentication and authorization tables. The portal migrations import existing admin, instructor, and student accounts into Shield and widen legacy password columns, including accounts created before Shield was installed. The attendance workflow migrations create `attendance_schedules` and `attendance_records`, hash legacy plaintext passwords, and add recurring weekdays without changing legacy one-time schedule rows. The initial-data seeder checks for the Shield tables before inserting any sample records.
5. To load the sample records and initial admin, set `ATTENDANCE_ADMIN_USERNAME`, `ATTENDANCE_ADMIN_EMAIL`, `ATTENDANCE_ADMIN_PASSWORD`, and `ATTENDANCE_INITIAL_PASSWORD` in the local environment, then run `php spark db:seed InitialData` once. The seed includes example programs, courses, instructors, students, instructor levels, and enrollment reference data. Do not use sample accounts in production.
6. Add program and course master records, instructor accounts, and instructor levels. Use the assignment panel on the Instructors page to map each instructor to the program/course they teach. Student enrollment requires the existing reference records in `year`, `school_year`, `semesters`, and `sections`.
7. Set `appTimezone` in `app/Config/App.php` to the campus timezone so schedule checks use campus local time. Point the web server document root to `public/`, then run locally with:

   ```sh
   php spark serve
   ```

The database config uses local-only fallback defaults and maps Wasmer's `DB_*` variables to CodeIgniter's default MySQL connection. Rotate any credentials shared in screenshots or chat, and keep all database credentials out of source control.

## Role access

- Public landing page: `/`
- Student sign-in: `/student`
- Instructor sign-in: `/instructor`
- Admin sign-in: `/myadmin/` (admin pages are under `/myadmin/...`; there is no public admin link)

Students and instructors sign in with their numeric account ID and password. Admins sign in with their username and password. Shield's user and identity tables hold the login credentials; role-prefixed Shield usernames map each account to its existing profile row. Role groups protect the `/student/...`, `/instructor/...`, and `/myadmin/...` routes. Existing profile password columns remain synchronized for compatibility, but login verification is performed by Shield.

The initial data seeder requires `ATTENDANCE_ADMIN_USERNAME`, `ATTENDANCE_ADMIN_EMAIL`, `ATTENDANCE_ADMIN_PASSWORD`, and `ATTENDANCE_INITIAL_PASSWORD` to be set in the local environment. Use passwords of at least 8 characters and do not add them to source control. The shared initial password is used only when creating seeded demo student/instructor accounts; change those passwords after setup.

## QR scanner integration

The QR scanner submission endpoint is not part of this application. Implement the scanner flow and its authentication/validation separately; the existing attendance schedule pages and records remain available.

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
4. Run `php spark migrate --all` on the clone. Confirm the Shield migration and portal-account import succeed, existing records remain intact, and valid legacy credentials authenticate. Only then repeat the reviewed baseline and migration steps against production during a planned backup window.
5. If any legacy migration effect is missing or uncertain, stop and write/review a corrective migration for that schema difference. Do not mark the missing migration as applied.

If an earlier `InitialData` seed attempt failed after inserting sample records because the Shield `users` table was missing, do not rerun the non-idempotent seeder. Run `php spark migrate --all`; the account synchronization migration will import the already-inserted profile accounts.

If the database already has a `migrations` table, preserve its history and reconcile only after reviewing its rows. Never clear the table, invent batches, or mark versions applied based on table names alone. For new installations, use the normal `php spark migrate` command on an empty database; no baseline step is needed.
