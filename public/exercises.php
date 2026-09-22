<?php

$exercises = [];
$errorMessage = null;

try {
    $pdo = require __DIR__ . '/../src/database.php';
    $stmt = $pdo->query('SELECT id, name FROM exercises ORDER BY name ASC');
    $exercises = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    $errorMessage = 'Unable to load exercises.';
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
        <h1>Exercises</h1>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        elseif (empty($exercises)): ?>
            <p>No exercises yet.</p>
        <?php
        else: ?>
            <ul>
                <?php
                foreach ($exercises as $exercise): ?>
                    <li><?= htmlspecialchars($exercise['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                <?php
                endforeach; ?>
            </ul>
        <?php
        endif; ?>
        <a href="index.php">Back to workouts</a>
    </body>
</html>