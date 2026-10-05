<?php

require_once __DIR__ . '/../src/bootstrap.php';

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
$csrfToken = csrfToken();

if ($errorMessage === null) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireValidCsrfToken($_POST['csrf_token'] ?? null);

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

$pageTitle = 'Remove exercise from workout';
require __DIR__ . '/../templates/header.php';
?>
        <h1>Remove exercise from workout</h1>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= e($errorMessage) ?></p>
        <?php
        else: ?>
            <?php
            if ($deleteErrorMessage !== null): ?>
                <p><?= e($deleteErrorMessage) ?></p>
            <?php
            endif; ?>
            <form action="workout-exercise-delete.php?id=<?= $workoutExerciseId ?>" method="post">
                <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken) ?>"
                >

                <div class="input-container">
                    <p><?= e($workoutExercise['exercise_name']) ?></p>
                </div>

                <div class="input-container">
                    <p><?= e($workoutExercise['workout_name']) ?></p>
                </div>

                <div class="input-container">
                    <p><?= e($workoutExercise['workout_date']) ?></p>
                </div>
                <p>This will remove this exercise and its recorded sets from this workout. The exercise will remain in
                    the catalog and other workouts.</p>
                <button type="submit">Remove exercise</button>
            </form>
            <a href="workout.php?id=<?= $workoutExercise['workout_id'] ?>">Cancel</a>
        <?php
        endif; ?>
        <a href="index.php">Back to workouts</a>
<?php require __DIR__ . '/../templates/footer.php'; ?>
