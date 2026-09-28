<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo 'Method not allowed.';
    exit;
}

session_start();

$sessionToken = $_SESSION['csrf_token'] ?? null;
$csrfToken = $_POST['csrf_token'] ?? null;

if (
    !is_string($sessionToken)
    || !is_string($csrfToken)
    || !hash_equals($sessionToken, $csrfToken)
) {
    http_response_code(403);
    echo 'Invalid form submission.';
    exit;
}

$rawId = $_POST['workout_exercise_id'] ?? null;

$workoutExerciseId = is_string($rawId)
    ? filter_var($rawId, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ])
    : false;

if ($workoutExerciseId === false) {
    http_response_code(400);
    echo 'Invalid workout exercise ID.';
    exit;
}

$direction = $_POST['direction'] ?? null;

if (
    !is_string($direction)
    || !in_array($direction, ['up', 'down'], true)
) {
    http_response_code(400);
    echo 'Invalid direction.';
    exit;
}
$pdo = null;
try {
    $pdo = require __DIR__ . '/../src/database.php';

    $sql = '
        SELECT
            id,
            workout_id,
            position
        FROM workout_exercises
        WHERE id = :id
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $workoutExerciseId,
    ]);

    $workoutExercise = $stmt->fetch();

    if ($workoutExercise === false) {
        http_response_code(404);
        echo 'Workout exercise not found.';
        exit;
    }

    $workoutId = (int)$workoutExercise['workout_id'];

    $pdo->beginTransaction();

    $sql = '
    SELECT
        id,
        position
    FROM workout_exercises
    WHERE workout_id = :workout_id
    ORDER BY position ASC
    FOR UPDATE
';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':workout_id' => $workoutId,
    ]);

    $lockedExercises = $stmt->fetchAll();

    $currentIndex = null;

    foreach ($lockedExercises as $index => $exercise) {
        if ((int)$exercise['id'] === $workoutExerciseId) {
            $currentIndex = $index;
            break;
        }
    }

    if ($currentIndex === null) {
        $pdo->rollBack();

        http_response_code(404);
        echo 'Workout exercise not found.';
        exit;
    }

    $neighborIndex = $direction === 'up'
        ? $currentIndex - 1
        : $currentIndex + 1;

    if (!isset($lockedExercises[$neighborIndex])) {
        $pdo->commit();

        header('Location: workout.php?id=' . $workoutId, true, 303);
        exit;
    }

    $currentExercise = $lockedExercises[$currentIndex];
    $neighborExercise = $lockedExercises[$neighborIndex];

    $currentPosition = (int) $currentExercise['position'];
    $neighborPosition = (int) $neighborExercise['position'];

    $lastExercise = $lockedExercises[count($lockedExercises) - 1];
    $temporaryPosition = (int) $lastExercise['position'] + 1;

    $sql = '
    UPDATE workout_exercises
    SET position = :position
    WHERE id = :id
      AND workout_id = :workout_id
';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':position' => $temporaryPosition,
        ':id' => (int) $currentExercise['id'],
        ':workout_id' => $workoutId,
    ]);

    $stmt->execute([
        ':position' => $currentPosition,
        ':id' => (int) $neighborExercise['id'],
        ':workout_id' => $workoutId,
    ]);

    $stmt->execute([
        ':position' => $neighborPosition,
        ':id' => (int) $currentExercise['id'],
        ':workout_id' => $workoutId,
    ]);

    $pdo->commit();

    header('Location: workout.php?id=' . $workoutId, true, 303);
    exit;
} catch (PDOException $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo 'Unable to change exercise order.';
    exit;
}