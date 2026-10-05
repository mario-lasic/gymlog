<?php

require_once __DIR__ . '/../src/bootstrap.php';

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


$pageTitle = 'GymLog';
require __DIR__ . '/../templates/header.php';
?>
        <h1>GymLog</h1>

        <?php
        if ($errorMessage !== null): ?>
            <p><?= e($errorMessage) ?></p>
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
                        <td><?= e($workout['workout_date']) ?></td>
                        <td><a href="workout.php?id=<?= $workout['id'] ?>"><?= e($workout['name']) ?></a></td>
                    </tr>
                <?php
                endforeach; ?>
            </table>
        <?php
        endif; ?>
        <a href="workout-create.php">Create Workout</a>
        <a href="exercises.php">Exercises</a>
<?php require __DIR__ . '/../templates/footer.php'; ?>
