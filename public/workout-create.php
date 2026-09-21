<?php

require_once __DIR__ . "/../src/workout-validation.php";

session_start();
date_default_timezone_set('Europe/Zagreb');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$name = '';
$note = '';
$date = date('Y-m-d');
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (
            !is_string($csrfToken)
            || !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        echo 'Invalid form submission.';
        exit;
    }

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
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
                content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>GymLog New Workout</title>
    </head>
    <body>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        endif; ?>
        <?php
        $csrfToken = $_SESSION['csrf_token'];
        $submitLabel = 'Create Workout';
        $formAction = 'workout-create.php';
        require __DIR__ . '/../templates/workout-form.php';
        ?>
        <a href="index.php">Back to workouts</a>
    </body>
</html>