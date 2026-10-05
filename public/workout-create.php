<?php

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/workout-validation.php';

$errors = [];
$name = '';
$note = '';
$date = date('Y-m-d');
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? null);

    $validation = validateWorkout($_POST);
    $date = $validation['date'];
    $name = $validation['name'];
    $note = $validation['note'];
    $errors = $validation['errors'];

    if (empty($errors)) {
        try {
            $pdo = require __DIR__ . '/../src/database.php';
            $sql = "INSERT INTO workouts(workout_date, name, note) VALUES (:workout_date, :name, :note)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                    ':workout_date' => $date,
                    ':name' => $name,
                    ':note' => $note === '' ? null : $note,
            ]);
            header('Location: /', true, 303);
            exit();
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage());
            $errorMessage = 'Unable to save workout.';
        }
    }
}

$pageTitle = 'GymLog New Workout';
require __DIR__ . '/../templates/header.php';
?>
        <h1>Create workout</h1>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= e($errorMessage) ?></p>
        <?php
        endif; ?>
        <?php
        $csrfToken = csrfToken();
        $submitLabel = 'Create Workout';
        $formAction = 'workout-create.php';

        require __DIR__ . '/../templates/workout-form.php';
        ?>
        <a href="index.php">Back to workouts</a>
<?php require __DIR__ . '/../templates/footer.php'; ?>
