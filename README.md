# GymLog

GymLog is a small workout log built as a learning project for PHP backend development. The application is developed step
by step, with an emphasis on understanding the code, database design, and Git workflow.

## Current status

- The MySQL schema contains four tables: `workouts`, `exercises`, `workout_exercises`, and `sets`.
- PHP connects to MySQL through PDO using a dedicated application account.
- The home page lists workouts by date, newest first, and displays an empty-state message when no workouts exist.
- Database failures return HTTP 500 and a generic message. Technical details are written to the PHP error log.
- Local database credentials are excluded from Git.

Workouts can now be created through a form with a date, name, and optional note. The application validates input on the
server, checks a session-based CSRF token, and saves valid data using a PDO prepared statement. Successful submission
redirects to the workout list.

Each workout has a details page displaying its date, name, and optional note. Invalid IDs return HTTP 400, while valid
IDs without a matching workout return HTTP 404. Output is escaped, and note line breaks are preserved.

Exercise and set entry, editing, and deletion are not implemented yet.

## Technologies and requirements

- PHP with the PDO MySQL extension.
- mbstring
- MySQL.
- HTML; CSS styling is planned.
- A web browser.

Development has been verified with PHP 8.5.9 and MySQL 8.4.11. PhpStorm is the main development tool, and MySQL runs
locally in Docker.

## Local setup

1. Start the local MySQL server.
2. For a fresh installation, execute `database/schema.sql` through an administrative connection in PhpStorm or DataGrip.
   This creates the `gymlog` database and its four tables. Skip this step if the schema has already been applied.
3. Create a separate MySQL account named `gymlog_app`, permitted to connect from your development environment. Grant it
   only `SELECT`, `INSERT`, `UPDATE`, and `DELETE` on `gymlog.*`.
4. Copy `config/database.example.php` to `config/database.local.php`.
5. Set the account password in the local copy and adjust the connection settings if necessary.

The default connection uses `127.0.0.1`, port `3306`, database `gymlog`, and character set `utf8mb4`.

Keep real credentials in the local configuration file. The example configuration contains no password.

## Run locally

From the project root, run:

```bash
php -S 127.0.0.1:8000 -t public
```

- `-S` starts PHP's built-in development server.
- `127.0.0.1:8000` makes it available locally on port 8000.
- `-t public` sets the public document root.

Open [GymLog locally](http://127.0.0.1:8000/). An empty database displays **No workouts yet**.
Displaying populated rows, checking their order, and verifying special-character escaping remain to be tested with
workout data.

Keep the terminal open while using the application. Press **Ctrl+C** to stop the server. This server is intended for
local development.

## Verification

Check PHP syntax from the project root:

```bash
php -l config/database.example.php
php -l config/database.local.php
php -l src/database.php
php -l public/index.php
php -l public/workout-create.php
php -l public/workout.php
```

The `-l` option checks syntax without executing the code.

Manual checks completed:

- An empty workout list displays **No workouts yet.**
- A valid workout is saved and displayed on the list.
- Refreshing the list after submission does not create another workout.
- Special characters in the workout name are preserved and displayed as text.
- An empty note is stored as SQL `NULL`.
- A name containing only spaces is rejected, while the entered note is retained.
- A modified CSRF token is rejected.
- Future dates, nonexistent calendar dates, and dates before `1000-01-01` are rejected.
- An invalid database configuration produces a generic error message when loading or saving workouts.
- An existing workout opens on its details page with HTTP 200.
- Missing or invalid workout IDs return HTTP 400.
- A valid but nonexistent workout ID returns HTTP 404.
- Special characters on the details page are displayed as text.

Sorting multiple workouts, including workouts with the same date, and additional field-length boundary checks remain to
be tested.

Database schema rules and the scope of their verification are documented in `docs/database-design.md`.

## First-version scope

- Create workouts with a date, name, and note.
- Add exercises to each workout.
- Record each set with repetitions and weight in kilograms.
- View workout history and details.
- Edit entries and confirm deletions.
- Use a simple interface suited to mobile screens.

The first version is intended for one user running the application locally. Authentication and access protection are
planned before public use with personal data.