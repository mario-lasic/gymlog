<?php

$workouts = [];
$errorMessage = null;

try {
    $pdo = require __DIR__ . '/../src/database.php';
    $stmt = $pdo->query('SELECT id, workout_date, name FROM workouts ORDER BY workout_date DESC ,id  DESC');
    $workouts = $stmt->fetchAll();
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
        elseif (empty($workouts)): ?>
            <p>No workouts yet.</p>
        <?php
        else: ?>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Workout</th>
                </tr>
                <?php
                foreach ($workouts as $workout): ?>
                    <tr>
                        <td><?= htmlspecialchars($workout['workout_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><a href="workout.php?id=<?= $workout['id'] ?>"><?= htmlspecialchars($workout['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></td>
                    </tr>
                <?php
                endforeach; ?>
            </table>
        <?php
        endif; ?>
        <a href="workout-create.php">Create Workout</a>
        <a href="exercises.php">Exercises</a>
    </body>
</html>