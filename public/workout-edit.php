<?php

require_once __DIR__ . "/../src/workout-validation.php";

session_start();
date_default_timezone_set('Europe/Zagreb');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$workout = null;
$errorMessage = null;
$saveErrorMessage = null;
$errors = [];

$rawId = $_GET['id'] ?? null;

$id = is_string($rawId)
        ? filter_var($rawId, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
        ])
        : false;

if ($id === false) {
    http_response_code(400);
    $errorMessage = 'Invalid workout ID.';
} else {
    try {
        $pdo = require __DIR__ . '/../src/database.php';
        $sql = 'SELECT id, workout_date, name, note FROM workouts WHERE id=:id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $workout = $stmt->fetch();
        if ($workout === false) {
            http_response_code(404);
            $errorMessage = "Workout not found.";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage());
        $errorMessage = 'Unable to load workout.';
    }
}
if ($errorMessage === null) {
    $date = $workout['workout_date'];
    $name = $workout['name'];
    $note = $workout['note'] ?? '';


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
                $sql = "UPDATE workouts SET workout_date=:workout_date, name=:name, note=:note WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                        ':workout_date' => $date,
                        ':name' => $name,
                        ':note' => $note === '' ? null : $note,
                        ':id' => $id,
                ]);
                header("Location: workout.php?id=$id", true, 303);
                exit();
            } catch (PDOException $e) {
                http_response_code(500);
                error_log('Database error: ' . $e->getMessage());
                $saveErrorMessage = 'Unable to save workout.';
            }
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
        <title>GymLog Edit Workout</title>
    </head>
    <body>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        else: ?>
            <?php
            if ($saveErrorMessage !== null): ?>
                <p><?= htmlspecialchars($saveErrorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php
            endif; ?>
            <?php
            $csrfToken = $_SESSION['csrf_token'];
            $submitLabel = 'Save changes';
            $formAction = "workout-edit.php?id=$id";
            require __DIR__ . '/../templates/workout-form.php';
            ?>
            <a href="workout.php?id=<?= $workout['id'] ?>">Cancel</a>
        <?php
        endif; ?>
        <a href="index.php">Back to workouts</a>
    </body>
</html>
