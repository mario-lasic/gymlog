<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$workoutExercise = null;
$errorMessage = null;
$saveErrorMessage = null;
$errors = [];

$reps = '';
$weightKg = '';
$repsValue = false;

$rawId = $_GET['workout_exercise_id'] ?? null;

$workoutExerciseId = is_string($rawId)
        ? filter_var($rawId, FILTER_VALIDATE_INT, [
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
                we.id AS workout_exercise_id,
                we.workout_id,
                e.name AS exercise_name,
                w.name AS workout_name,
                w.workout_date
            FROM workout_exercises AS we
            INNER JOIN workouts AS w
                ON w.id = we.workout_id
            INNER JOIN exercises AS e
                ON e.id = we.exercise_id
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

    $rawReps = $_POST['reps'] ?? null;

    if (!is_string($rawReps)) {
        $errors['reps'] = 'Enter valid reps.';
    } else {
        $reps = trim($rawReps);

        $repsValue = filter_var($reps, FILTER_VALIDATE_INT, [
                'options' => [
                        'min_range' => 1,
                        'max_range' => 65535,
                ],
        ]);

        if ($repsValue === false) {
            $errors['reps'] =
                    'Reps must be a whole number between 1 and 65535.';
        }
    }

    $rawWeightKg = $_POST['weight_kg'] ?? null;

    if (!is_string($rawWeightKg)) {
        $errors['weight_kg'] = 'Enter a valid weight.';
    } else {
        $weightKg = trim($rawWeightKg);

        if (
                preg_match(
                        '/\A[0-9]{1,4}(?:\.[0-9]{1,2})?\z/',
                        $weightKg
                ) !== 1
        ) {
            $errors['weight_kg'] =
                    'Weight must be between 0 and 9999.99 kg, '
                    . 'with at most two decimal places.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Zaključavamo vezu prije dodjele broja serije.
            $sql = '
                SELECT id
                FROM workout_exercises
                WHERE id = :id
                FOR UPDATE
            ';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                    ':id' => $workoutExerciseId,
            ]);

            $lockedId = $stmt->fetchColumn();

            if ($lockedId === false) {
                $pdo->rollBack();

                http_response_code(404);
                $errorMessage = 'Workout exercise not found.';
            } else {
                $sql = '
                    SELECT COALESCE(MAX(set_number), 0)
                    FROM sets
                    WHERE workout_exercise_id = :workout_exercise_id
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                        ':workout_exercise_id' => $workoutExerciseId,
                ]);

                $nextSetNumber = (int)$stmt->fetchColumn() + 1;

                if ($nextSetNumber > 65535) {
                    $pdo->rollBack();
                    $saveErrorMessage = 'Maximum number of sets reached.';
                } else {
                    $sql = '
                        INSERT INTO sets (
                            workout_exercise_id,
                            set_number,
                            reps,
                            weight_kg
                        )
                        VALUES (
                            :workout_exercise_id,
                            :set_number,
                            :reps,
                            :weight_kg
                        )
                    ';

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                            ':workout_exercise_id' => $workoutExerciseId,
                            ':set_number' => $nextSetNumber,
                            ':reps' => $repsValue,
                            ':weight_kg' => $weightKg,
                    ]);

                    $pdo->commit();

                    header(
                            'Location: workout.php?id='
                            . (int)$workoutExercise['workout_id'],
                            true,
                            303
                    );
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            http_response_code(500);
            error_log('Database error: ' . $e->getMessage());
            $saveErrorMessage = 'Unable to save set.';
        }
    }
}

$csrfToken = $_SESSION['csrf_token'];
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>GymLog Add Set</title>
    </head>
    <body>
        <h1>Add set</h1>

        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars(
                        $errorMessage,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>
        <?php
        else: ?>
            <h2><?= htmlspecialchars(
                        $workoutExercise['exercise_name'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></h2>

            <p><?= htmlspecialchars(
                        $workoutExercise['workout_name'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>

            <p><?= htmlspecialchars(
                        $workoutExercise['workout_date'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>

            <?php
            if ($saveErrorMessage !== null): ?>
                <p><?= htmlspecialchars(
                            $saveErrorMessage,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                    ) ?></p>
            <?php
            endif; ?>

            <form
                    action="set-create.php?workout_exercise_id=<?= $workoutExerciseId ?>"
                    method="post"
            >
                <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                                $csrfToken,
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                        ) ?>"
                >

                <div class="input-container">
                    <label for="reps">Reps</label>
                    <input
                            type="number"
                            id="reps"
                            name="reps"
                            min="1"
                            max="65535"
                            step="1"
                            value="<?= htmlspecialchars(
                                    $reps,
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                            ) ?>"
                            required
                    >

                    <?php
                    if (isset($errors['reps'])): ?>
                        <p><?= htmlspecialchars(
                                    $errors['reps'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                            ) ?></p>
                    <?php
                    endif; ?>
                </div>

                <div class="input-container">
                    <label for="weight_kg">Weight (kg)</label>
                    <input
                            type="number"
                            id="weight_kg"
                            name="weight_kg"
                            min="0"
                            max="9999.99"
                            step="0.01"
                            value="<?= htmlspecialchars(
                                    $weightKg,
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                            ) ?>"
                            required
                    >

                    <?php
                    if (isset($errors['weight_kg'])): ?>
                        <p><?= htmlspecialchars(
                                    $errors['weight_kg'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                            ) ?></p>
                    <?php
                    endif; ?>
                </div>

                <button type="submit">Save set</button>
            </form>

            <a href="workout.php?id=<?= (int)$workoutExercise['workout_id'] ?>">
                Cancel
            </a>
        <?php
        endif; ?>

        <a href="index.php">Back to workouts</a>
    </body>
</html>