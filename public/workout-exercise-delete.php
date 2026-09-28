<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$workoutExercise = null;
$errorMessage = null;
$deleteErrorMessage = null;

$rawWorkoutExerciseId = $_GET['id'] ?? null;

$workoutExerciseId = is_string($rawWorkoutExerciseId)
        ? filter_var($rawWorkoutExerciseId, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
        ])
        : false;

if ($workoutExerciseId === false) {
    http_response_code(400);
    $errorMessage = 'Invalid workout exercise ID.';
} else {
    try {
        $pdo = require __DIR__ . '/../src/database.php';

        $sql = '
            SELECT
                we.id,
                we.workout_id,
                we.position,
                e.name AS exercise_name,
                w.name AS workout_name,
                w.workout_date
            FROM workout_exercises AS we
            INNER JOIN exercises AS e
                ON we.exercise_id = e.id
            INNER JOIN workouts AS w
                ON we.workout_id = w.id
            WHERE we.id = :id
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
                ':id' => $workoutExerciseId,
        ]);

        $workoutExercise = $stmt->fetch();

        if ($workoutExercise === false) {
            http_response_code(404);
            $errorMessage = 'Workout exercise not found.';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage());
        $errorMessage = 'Unable to load workout exercise.';
    }
}
$csrfToken = $_SESSION['csrf_token'];

if ($errorMessage === null) {
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

        try {
            $sql = "DELETE FROM workout_exercises WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                    'id' => $workoutExerciseId
            ]);
            header('Location: /workout.php?id='.$workoutExercise['workout_id'], true, 303);
            exit();
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage());
            $deleteErrorMessage = 'Unable to remove exercise from workout.';
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
        <title>Remove exercise from workout</title>
    </head>
    <body>
        <h1>Remove exercise from workout</h1>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        else: ?>
            <?php
            if ($deleteErrorMessage !== null): ?>
                <p><?= htmlspecialchars($deleteErrorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php
            endif; ?>
            <form action="workout-exercise-delete.php?id=<?= $workoutExerciseId ?>" method="post">
                <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                >

                <div class="input-container">
                    <p><?= htmlspecialchars(
                                $workoutExercise['exercise_name'],
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                        ) ?></p>
                </div>

                <div class="input-container">
                    <p><?= htmlspecialchars(
                                $workoutExercise['workout_name'],
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                        ) ?></p>
                </div>

                <div class="input-container">
                    <p><?= htmlspecialchars(
                                $workoutExercise['workout_date'],
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                        ) ?></p>
                </div>
                <p>This will remove this exercise and its recorded sets from this workout. The exercise will remain in
                    the catalog and other workouts.</p>
                <button type="submit">Remove exercise</button>
            </form>
            <a href="workout.php?id=<?= $workoutExercise['workout_id'] ?>">Cancel</a>
        <?php
        endif; ?>
        <a href="index.php">Back to workouts</a>
    </body>
</html>
