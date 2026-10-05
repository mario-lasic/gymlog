<?php

require_once __DIR__ . '/../src/bootstrap.php';

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


$pageTitle = 'GymLog Exercises';
require __DIR__ . '/../templates/header.php';
?>
        <h1>Exercises</h1>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= e($errorMessage) ?></p>
        <?php
        elseif (empty($exercises)): ?>
            <p>No exercises yet.</p>
        <?php
        else: ?>
            <ul>
                <?php
                foreach ($exercises as $exercise): ?>
                    <li><?= e($exercise['name']) ?></li>
                <?php
                endforeach; ?>
            </ul>
        <?php
        endif; ?>
        <a href="exercise-create.php">Create exercise</a>
        <a href="index.php">Back to workouts</a>
<?php require __DIR__ . '/../templates/footer.php'; ?>
