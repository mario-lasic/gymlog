<?php

$workoutCount = null;
$errorMessage = null;

try {
    $pdo = require __DIR__ . '/../src/database.php';
    $stmt = $pdo->query('SELECT COUNT(*) FROM workouts');
    $workoutCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    $errorMessage = 'Unable to load workouts.';
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
                content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>GymLog</title>
    </head>
    <body>
        <h1>GymLog</h1>

        <?php
        if ($errorMessage !== null): ?>
            <p><?= $errorMessage ?></p>
        <?php
        else: ?>
            <p>Workouts: <?= $workoutCount ?></p>
        <?php
        endif; ?>
    </body>
</html>