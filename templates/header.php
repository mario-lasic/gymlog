<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($pageTitle ?? 'GymLog') ?></title>
        <link rel="stylesheet" href="/assets/css/style.css">
    </head>
    <body>
        <header class="site-header">
            <a class="site-brand" href="/index.php">GymLog</a>

            <nav aria-label="Main navigation">
                <a href="/index.php">Workouts</a>
                <a href="/exercises.php">Exercises</a>
            </nav>
        </header>

        <main class="container">
