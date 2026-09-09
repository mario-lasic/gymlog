# GymLog

GymLog is a small workout log built as a learning project for PHP backend development. The aim is to build a complete application step by step and understand how each part works.

## Current status

The project is in its initial setup stage. The first PHP page runs locally and displays the heading **GymLog** and the message **PHP works!**.

Workout tracking and database integration are planned features and have not been implemented yet.

## Technologies

- **Current:** PHP and HTML.
- **Planned:** MySQL accessed through PDO, with CSS for styling.
- **Development tools:** PhpStorm and Git.

## Requirements

The current page requires a PHP CLI installation and a web browser. Development and initial verification were performed with **PHP 8.5.9**.

## Run locally

Open a terminal in the project root, which is the directory containing the `public` folder, and run:

```bash
php -S 127.0.0.1:8000 -t public
```

- `-S` starts PHP's built-in development server.
- `127.0.0.1:8000` makes it available on this computer at port 8000.
- `-t public` sets `public` as the document root: the directory from which the server serves pages.

Keep the terminal open and visit [GymLog locally](http://127.0.0.1:8000/).

Check that the page displays **GymLog** and **PHP works!**. Press **Ctrl+C** in the server's terminal to stop it.

This server is intended for local development.

## Check PHP syntax

From the project root, run:

```bash
php -l public/index.php
```

The expected result is:

```text
No syntax errors detected in public/index.php
```

The `-l` option checks PHP syntax without executing the page. The browser check above verifies its output.

## Planned first version

- Create workouts with a date, name, and note.
- Add exercises to each workout.
- Record each set with repetitions and weight in kilograms.
- View workout history and details.
- Edit entries and confirm deletions.
- Use the application through a simple interface suited to mobile screens.

The first version is intended for one user running the application locally. Authentication and access protection are planned before public use with personal data.
