<?php

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/set-validation.php';

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
    requireValidCsrfToken($_POST['csrf_token'] ?? null);

    $validation = validateSet($_POST);
    $reps = $validation['reps'];
    $repsValue = $validation['reps_value'];
    $weightKg = $validation['weight_kg'];
    $errors = $validation['errors'];

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

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

$csrfToken = csrfToken();

$pageTitle = 'GymLog Add Set';
require __DIR__ . '/../templates/header.php';
?>
        <h1>Add set</h1>

        <?php
        if ($errorMessage !== null): ?>
            <p><?= e($errorMessage) ?></p>
        <?php
        else: ?>
            <h2><?= e($workoutExercise['exercise_name']) ?></h2>

            <p><?= e($workoutExercise['workout_name']) ?></p>

            <p><?= e($workoutExercise['workout_date']) ?></p>

            <?php
            if ($saveErrorMessage !== null): ?>
                <p><?= e($saveErrorMessage) ?></p>
            <?php
            endif; ?>

            <form
                    action="set-create.php?workout_exercise_id=<?= $workoutExerciseId ?>"
                    method="post"
            >
                <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken) ?>"
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
                            value="<?= e($reps) ?>"
                            required
                    >

                    <?php
                    if (isset($errors['reps'])): ?>
                        <p><?= e($errors['reps']) ?></p>
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
                            value="<?= e($weightKg) ?>"
                            required
                    >

                    <?php
                    if (isset($errors['weight_kg'])): ?>
                        <p><?= e($errors['weight_kg']) ?></p>
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
<?php require __DIR__ . '/../templates/footer.php'; ?>
