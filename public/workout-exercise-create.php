<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$workout = null;
$exercises = [];
$errorMessage = null;
$exerciseId = null;
$errors = [];
$saveErrorMessage = null;

$rawWorkoutId = $_GET['workout_id'] ?? null;

$workoutId = is_string($rawWorkoutId)
        ? filter_var($rawWorkoutId, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
        ])
        : false;

if ($workoutId === false) {
    http_response_code(400);
    $errorMessage = 'Invalid workout ID.';
} else {
    try {
        $pdo = require __DIR__ . '/../src/database.php';

        $stmt = $pdo->prepare(
                'SELECT id, workout_date, name FROM workouts WHERE id = :id'
        );
        $stmt->execute([':id' => $workoutId]);
        $workout = $stmt->fetch();

        if ($workout === false) {
            http_response_code(404);
            $errorMessage = 'Workout not found.';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage());
        $errorMessage = 'Unable to load workout.';
    }

    if ($errorMessage === null) {
        try {
            $stmt = $pdo->query(
                    'SELECT id, name FROM exercises ORDER BY name ASC'
            );
            $exercises = $stmt->fetchAll();
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage());
            $errorMessage = 'Unable to load exercises.';
        }
    }
}

if ($errorMessage === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (
            !is_string($csrfToken)
            || !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        echo 'Invalid form submission.';
        exit;
    }

    $rawExerciseId = $_POST['exercise_id'] ?? null;

    $exerciseId = is_string($rawExerciseId)
            ? filter_var($rawExerciseId, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
            ])
            : false;

    if ($exerciseId === false) {
        $errors['exercise_id'] = 'Choose a valid exercise.';
    } else {
        $exerciseExists = false;

        foreach ($exercises as $exercise) {
            if ((int)$exercise['id'] === $exerciseId) {
                $exerciseExists = true;
                break;
            }
        }

        if (!$exerciseExists) {
            $errors['exercise_id'] = 'Choose a valid exercise.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                    'SELECT EXISTS(
                    SELECT 1
                    FROM workout_exercises
                    WHERE workout_id = :workout_id
                      AND exercise_id = :exercise_id
                )'
            );

            $stmt->execute([
                    ':workout_id' => $workoutId,
                    ':exercise_id' => $exerciseId,
            ]);

            $alreadyAdded = (int)$stmt->fetchColumn() === 1;

            if ($alreadyAdded) {
                $errors['exercise_id'] =
                        'This exercise is already in the workout.';
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage());
            $errorMessage = 'Unable to check workout exercises.';
        }
    }
    if ($errorMessage === null && empty($errors)) {
        try {
            $sql = "SELECT COALESCE(MAX(position), 0) FROM workout_exercises WHERE workout_id = :workout_id";
            $stmt = $pdo -> prepare($sql);
            $stmt->execute([
                    ':workout_id' => $workoutId
            ]);
            $nextPosition = (int)($stmt->fetchColumn() + 1);
            $sql = "INSERT INTO workout_exercises(workout_id, exercise_id, position) VALUES (:workout_id, :exercise_id, :position)";
            $stmt = $pdo -> prepare($sql);
            $stmt->execute([
                    ':workout_id' => $workoutId,
                    ':exercise_id' => $exerciseId,
                    ':position' => $nextPosition
            ]);
            header("Location: workout.php?id=$workoutId", true, 303);
            exit;
        } catch (PDOException $e) {
            if(($e->errorInfo[1] ?? null) === 1062) {
                $saveErrorMessage = "Unable to add exercise because the workout changed. Reload the page and try again.";
            } else {
                http_response_code(500);
                error_log('Database error: ' . $e->getMessage());
                $saveErrorMessage = 'Unable to save workout exercises.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>GymLog Add Exercise to Workout</title>
    </head>
    <body>
        <h1>Add exercise to workout</h1>

        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars(
                        $errorMessage,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>
        <?php
        else: ?>
            <p><?= htmlspecialchars(
                        $workout['name'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>

            <p><?= htmlspecialchars(
                        $workout['workout_date'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>

            <?php
            if (empty($exercises)): ?>
                <p>No exercises available. Create an exercise first.</p>
                <a href="exercise-create.php">Create exercise</a>

                <?php
                if (isset($errors['exercise_id'])): ?>
                    <p><?= htmlspecialchars(
                                $errors['exercise_id'],
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                        ) ?></p>
                <?php
                endif; ?>
            <?php
            else: ?>
                <?php if($saveErrorMessage !== null): ?>
                    <p><?= htmlspecialchars($saveErrorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                <?php endif; ?>
                <form
                        action="workout-exercise-create.php?workout_id=<?= $workoutId ?>"
                        method="post"
                >
                    <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                    $_SESSION['csrf_token'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                            ) ?>"
                    >

                    <div class="input-container">
                        <label for="exercise_id">Exercise</label>

                        <select name="exercise_id" id="exercise_id" required>
                            <option value="">Choose an exercise</option>

                            <?php
                            foreach ($exercises as $exercise): ?>
                                <option
                                        value="<?= (int)$exercise['id'] ?>"
                                        <?= (int)$exercise['id'] === $exerciseId
                                                ? 'selected'
                                                : '' ?>
                                ><?= htmlspecialchars(
                                            $exercise['name'],
                                            ENT_QUOTES | ENT_SUBSTITUTE,
                                            'UTF-8'
                                    ) ?></option>
                            <?php
                            endforeach; ?>
                        </select>

                        <?php
                        if (isset($errors['exercise_id'])): ?>
                            <p><?= htmlspecialchars(
                                        $errors['exercise_id'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                ) ?></p>
                        <?php
                        endif; ?>
                    </div>

                    <button type="submit">Add exercise</button>
                </form>
            <?php
            endif; ?>

            <a href="workout.php?id=<?= $workoutId ?>">Cancel</a>
        <?php
        endif; ?>

        <a href="index.php">Back to workouts</a>
    </body>
</html>