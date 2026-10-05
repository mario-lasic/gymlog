<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$workout = null;
$errorMessage = null;
$workoutExercises = [];
$setsByWorkoutExercise = [];

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

        $sql = '
            SELECT
                id,
                workout_date,
                name,
                note
            FROM workouts
            WHERE id = :id
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
                ':id' => $id,
        ]);

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
            $sql = '
                SELECT
                    we.id AS workout_exercise_id,
                    we.position,
                    e.name
                FROM workout_exercises AS we
                INNER JOIN exercises AS e
                    ON we.exercise_id = e.id
                WHERE we.workout_id = :workout_id
                ORDER BY we.position ASC
            ';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                    ':workout_id' => $id,
            ]);

            $workoutExercises = $stmt->fetchAll();
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage());
            $errorMessage = 'Unable to load workout exercises.';
        }
    }

    if ($errorMessage === null) {
        try {
            $sql = '
                SELECT
                    s.id,
                    s.workout_exercise_id,
                    s.set_number,
                    s.reps,
                    s.weight_kg
                FROM sets AS s
                INNER JOIN workout_exercises AS we
                    ON s.workout_exercise_id = we.id
                WHERE we.workout_id = :workout_id
                ORDER BY we.position ASC, s.set_number ASC
            ';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                    ':workout_id' => $id,
            ]);

            $sets = $stmt->fetchAll();

            foreach ($sets as $set) {
                $workoutExerciseId = (int)$set['workout_exercise_id'];
                $setsByWorkoutExercise[$workoutExerciseId][] = $set;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage());
            $errorMessage = 'Unable to load workout sets.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>GymLog Workout</title>
    </head>
    <body>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars(
                        $errorMessage,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>
        <?php
        else: ?>
            <h1><?= htmlspecialchars(
                        $workout['name'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></h1>

            <p><?= htmlspecialchars(
                        $workout['workout_date'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>

            <?php
            if ($workout['note'] === null || $workout['note'] === ''): ?>
                <p>No notes</p>
            <?php
            else: ?>
                <p><?= nl2br(
                            htmlspecialchars(
                                    $workout['note'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                            )
                    ) ?></p>
            <?php
            endif; ?>

            <h2>Exercises</h2>

            <?php
            if (empty($workoutExercises)): ?>
                <p>No exercises added to this workout yet.</p>
            <?php
            else: ?>
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Position</th>
                            <th scope="col">Exercise</th>
                            <th scope="col">Sets</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($workoutExercises as $workoutExercise): ?>
                            <?php
                            $workoutExerciseId =
                                    (int)$workoutExercise['workout_exercise_id'];

                            $exerciseSets =
                                    $setsByWorkoutExercise[$workoutExerciseId] ?? [];
                            ?>

                            <tr>
                                <td><?= (int)$workoutExercise['position'] ?></td>

                                <td><?= htmlspecialchars(
                                            $workoutExercise['name'],
                                            ENT_QUOTES | ENT_SUBSTITUTE,
                                            'UTF-8'
                                    ) ?></td>

                                <td>
                                    <?php
                                    if (empty($exerciseSets)): ?>
                                        <p>No sets yet.</p>
                                    <?php
                                    else: ?>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th scope="col">Set</th>
                                                    <th scope="col">Reps</th>
                                                    <th scope="col">Weight (kg)</th>
                                                    <th scope="col">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach ($exerciseSets as $set): ?>
                                                    <tr>
                                                        <td><?= (int)$set['set_number'] ?></td>
                                                        <td><?= (int)$set['reps'] ?></td>
                                                        <td><?= htmlspecialchars(
                                                                    $set['weight_kg'],
                                                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                                                    'UTF-8'
                                                            ) ?></td>
                                                        <td>
                                                            <a href="set-edit.php?id=<?= (int) $set['id'] ?>">Edit</a>
                                                            <a href="set-delete.php?id=<?= (int) $set['id'] ?>">Delete</a>
                                                        </td>
                                                    </tr>
                                                <?php
                                                endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php
                                    endif; ?>
                                </td>

                                <td>
                                    <a href="workout-exercise-delete.php?id=<?= $workoutExerciseId ?>">
                                        Remove
                                    </a>

                                    <form action="workout-exercise-move.php" method="post">
                                        <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars(
                                                        $csrfToken,
                                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                                        'UTF-8'
                                                ) ?>"
                                        >

                                        <input
                                                type="hidden"
                                                name="workout_exercise_id"
                                                value="<?= $workoutExerciseId ?>"
                                        >

                                        <button type="submit" name="direction" value="up">
                                            Move Up
                                        </button>

                                        <button type="submit" name="direction" value="down">
                                            Move Down
                                        </button>
                                    </form>

                                    <a href="set-create.php?workout_exercise_id=<?= $workoutExerciseId ?>">
                                        Add set
                                    </a>
                                </td>
                            </tr>
                        <?php
                        endforeach; ?>
                    </tbody>
                </table>
            <?php
            endif; ?>

            <a href="workout-edit.php?id=<?= $id ?>">Edit workout</a>
            <a href="workout-delete.php?id=<?= $id ?>">Delete workout</a>
            <a href="workout-exercise-create.php?workout_id=<?= $id ?>">
                Add exercise
            </a>
        <?php
        endif; ?>

        <a href="index.php">Back to workouts</a>
    </body>
</html>