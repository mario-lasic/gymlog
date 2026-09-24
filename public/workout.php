<?php

$workout = null;
$errorMessage = null;
$workoutExercises = [];

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
        } else {
            try {
                $sql = "SELECT we.id AS workout_exercise_id, we.position, e.name FROM workout_exercises AS we INNER JOIN exercises AS e ON we.exercise_id = e.id WHERE we.workout_id = :id ORDER BY we.position ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                        'id' => $id
                ]);
                $workoutExercises = $stmt->fetchAll();
            } catch (PDOException $e) {
                http_response_code(500);
                error_log('Database error: ' . $e->getMessage());
                $errorMessage = 'Unable to load workout exercises.';
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage());
        $errorMessage = 'Unable to load workout.';
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
        <title>GymLog</title>
    </head>
    <body>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        else: ?>
            <h1><?= htmlspecialchars($workout['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($workout['workout_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php
            if ($workout['note'] === null || $workout['note'] === ''): ?>
                <p>No notes</p>
            <?php
            else: ?>
                <p><?= nl2br(htmlspecialchars($workout['note'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></p>
            <?php
            endif; ?>
            <h2>Exercises</h2>
            <?php
            if (empty($workoutExercises)): ?>
                <p>No exercises added to this workout yet.</p>
            <?php
            else: ?>
                <table>
                    <tr>
                        <th>Position</th>
                        <th>Exercise</th>
                    </tr>
                    <?php
                    foreach ($workoutExercises as $workoutExercise): ?>
                        <tr>
                            <td><?= htmlspecialchars(
                                        $workoutExercise['position'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                ) ?></td>
                            <td><?= htmlspecialchars(
                                        $workoutExercise['name'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                ) ?></td>
                        </tr>
                    <?php
                    endforeach; ?>
                </table>
            <?php
            endif; ?>
            <a href="workout-edit.php?id=<?= $workout['id'] ?>">Edit workout</a>
            <a href="workout-delete.php?id=<?= $workout['id'] ?>">Delete Workout</a>
            <a href="workout-exercise-create.php?workout_id=<?= $id ?>">Add exercise</a>
        <?php
        endif; ?>
        <a href="/">Back to workouts</a>
    </body>
</html>
