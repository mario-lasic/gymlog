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

Existing workouts can be edited through a prefilled form. Updates use server-side validation, CSRF protection, and a PDO
prepared statement. Validation errors preserve entered values, and successful updates redirect to the same workout's
details page.

Workouts can be deleted through a confirmation page. Deletion requires a POST request and a valid CSRF token. Successful
deletion redirects to the workout list.

The exercise catalog lists exercise names alphabetically and supports creating exercises with name validation, CSRF
protection, and duplicate handling.

Existing catalog exercises can be added to workouts. Each exercise can appear once per workout, and new entries are
appended after the highest existing position. Workout details display the linked exercises in position order, with an
empty-state message when none have been added.

Individual exercises can be removed from a workout through a confirmation page using POST and CSRF protection. The
catalog exercise and its entries in other workouts remain unchanged. Remaining exercise positions are preserved.

Exercises can be moved up or down within a workout using POST requests with CSRF protection. Position swaps use a
database transaction and row locking. Moving beyond the first or last exercise leaves the order unchanged.

Manual checks confirmed movement in both directions, unchanged boundary positions, preserved entry IDs and row counts,
and no changes to other workouts. No temporary position remains after a successful swap.

Sets can be recorded for individual exercises within a workout, with repetitions and weight in kilograms. Input is
validated on the server and protected with CSRF tokens. Set numbers are assigned automatically using a transaction and a
lock on the parent workout exercise.

Workout details display each exercise's sets in set-number order, including an empty-state message. Individual sets can
be edited or deleted through a confirmation page.

Workout creation and editing share validation in `src/workout-validation.php` and a form template in
`templates/workout-form.php`. Set creation and editing share validation in `src/set-validation.php`.

All pages load `src/bootstrap.php` for session initialization, the application time zone, and shared helpers. CSRF token
generation and verification are centralized in `src/csrf.php`. The `e()` helper provides consistent HTML escaping.

HTML pages share a header, navigation, and footer through `templates/header.php` and `templates/footer.php`. Each page
retains its own request handling, database operations, and redirects.

The interface uses plain CSS with responsive navigation, styled forms, visible keyboard focus, and distinct destructive
actions. Wide workout tables scroll horizontally within their container on smaller screens.

## Technologies and requirements

- PHP with the PDO MySQL extension.
- mbstring
- MySQL.
- HTML and CSS.
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
php -l public/workout-edit.php
php -l src/workout-validation.php
php -l templates/workout-form.php
php -l public/workout-delete.php
php -l public/exercises.php
php -l public/exercise-create.php
php -l public/workout-exercise-create.php
php -l public/workout-exercise-delete.php
php -l public/workout-exercise-move.php
php -l public/set-create.php
php -l src/set-validation.php
php -l public/set-edit.php
php -l public/set-delete.php
php -l src/bootstrap.php
php -l src/helpers.php
php -l src/csrf.php
php -l templates/header.php
php -l templates/footer.php
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
- Editing updates the existing workout without creating another record.
- Saving unchanged values succeeds.
- Clearing the note stores SQL `NULL`.
- Invalid edit input preserves entered values without updating the database.
- An altered CSRF token on the edit form returns HTTP 403.
- Submitting the edit form for a nonexistent workout returns HTTP 404 without saving.
- Opening, refreshing, or cancelling the deletion confirmation leaves the workout unchanged.
- An altered CSRF token prevents deletion and returns HTTP 403.
- A valid deletion removes only the selected test workout and returns to the list.
- Opening the deleted workout's details returns HTTP 404.
- A valid exercise name is saved in the catalog.
- Duplicate names, including submissions with surrounding spaces, are rejected without creating another record.
- Names containing only spaces are rejected.
- An altered CSRF token prevents exercise creation and returns HTTP 403.
- The first two exercises added to an empty workout receive positions 1 and 2.
- Adding the same exercise twice to one workout is rejected.
- The same catalog exercise can be added to another workout.
- Workout details display only that workout's exercises, ordered by position.
- An empty workout displays an empty-state message and an Add exercise link.
- Invalid or nonexistent workouts do not display the Add exercise link.
- Refreshing workout details after adding an exercise does not create another entry.
- An altered CSRF token prevents removal and returns HTTP 403.
- Removing an exercise deletes only the selected workout entry and redirects to its workout.
- The catalog exercise and its entry in another workout remain unchanged.
- Remaining exercise positions are preserved.
- Opening the removed entry's confirmation page returns HTTP 404.
- Sets receive numbers 1 and 2 within one workout exercise; another exercise starts at 1.
- Weights of 0 kg and 62.50 kg are saved and displayed correctly.
- Invalid repetitions, negative weights, and weights with more than two decimal places are rejected.
- An altered CSRF token prevents saving.
- Refreshing after successful submission does not create another set.
- Sets appear under the correct exercise and workout, ordered by set number.
- Exercises without sets display No sets yet.
- Editing a set updates repetitions and weight while preserving its ID and set number.
- Saving unchanged values and a weight of 0 kg succeeds.
- Invalid edit input preserves entered values without changing the database.
- Altered CSRF tokens prevent both editing and deletion.
- Opening or cancelling the deletion confirmation leaves the set unchanged.
- Deleting a set preserves other sets and the workout exercise.
- Invalid set IDs return HTTP 400; nonexistent set IDs return HTTP 404.
- Set creation still works after extracting shared validation.
- Pages and forms display correctly on desktop and at a mobile viewport width of approximately 390 px.
- Wide tables scroll within their container without widening the page.
- Destructive buttons are visually distinct.
- Keyboard navigation displays a visible focus outline.

### Final verification (2026-10-05)

The core features of the first local version are implemented. Earlier manual checks confirmed the complete workflow
for workouts, exercise entries, and sets, including editing, deletion, redirects, and mobile table scrolling.

Final checks passed:

- Syntax checks for PHP source, pages, templates, and the example configuration.
- 52 validation and HTML-escaping checks, including Unicode field-length limits, calendar dates, repetitions, and weights.
- 100 HTTP and database-structure checks using a temporary application copy with read-only MySQL connections.
- 8 browser layout checks at viewport widths of 390 and 1280 px on the home page, catalog, and creation forms.
- Inspection of the live MySQL 8.4.11 schema confirmed the expected InnoDB tables, collation, CHECK constraints, unique
  indexes, and foreign-key deletion rules.
- The local configuration is ignored by Git and does not appear in the accessible repository history.

The read-only checks did not repeat successful database writes. Cascade deletion, rollback after an injected failure,
concurrent requests, the maximum set number, and sorting several workouts with the same date remain untested in a
separate database. Populated workout tables rely on the earlier manual verification. These limits are recorded rather
than treated as passed tests.

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
