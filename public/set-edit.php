<?php

require_once __DIR__ . '/../src/set-validation.php';

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$set = null;
$errorMessage = null;
$saveErrorMessage = null;
$errors = [];
$reps = '';
$weightKg = '';

$rawId = $_GET['id'] ?? null;

$setId = is_string($rawId)
        ? filter_var($rawId, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
        ])
        : false;

if ($setId === false) {
    http_response_code(400);
    $errorMessage = 'Invalid set ID.';
} else {
    try {
        $pdo = require __DIR__ . '/../src/database.php';

        $sql = '
            SELECT
                s.id,
                s.set_number,
                s.reps,
                s.weight_kg,
                we.workout_id,
                e.name AS exercise_name,
                w.name AS workout_name,
                w.workout_date
            FROM sets AS s
            INNER JOIN workout_exercises AS we
                ON s.workout_exercise_id = we.id
            INNER JOIN exercises AS e
                ON we.exercise_id = e.id
            INNER JOIN workouts AS w
                ON we.workout_id = w.id
            WHERE s.id = :id
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $setId]);
        $set = $stmt->fetch();

        if ($set === false) {
            http_response_code(404);
            $errorMessage = 'Set not found.';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage());
        $errorMessage = 'Unable to load set.';
    }
}

if ($errorMessage === null) {
    $reps = (string)$set['reps'];
    $weightKg = (string)$set['weight_kg'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = $_POST['csrf_token'] ?? null;

        if (
                !is_string($submittedToken)
                || !hash_equals($_SESSION['csrf_token'], $submittedToken)
        ) {
            http_response_code(403);
            echo 'Invalid form submission.';
            exit;
        }

        $validation = validateSet($_POST);
        $reps = $validation['reps'];
        $repsValue = $validation['reps_value'];
        $weightKg = $validation['weight_kg'];
        $errors = $validation['errors'];

        if (empty($errors)) {
            try {
                $sql = '
                    UPDATE sets
                    SET
                        reps = :reps,
                        weight_kg = :weight_kg
                    WHERE id = :id
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                        ':reps' => $repsValue,
                        ':weight_kg' => $weightKg,
                        ':id' => $setId,
                ]);

                header(
                        'Location: workout.php?id=' . (int)$set['workout_id'],
                        true,
                        303
                );
                exit;
            } catch (PDOException $e) {
                http_response_code(500);
                error_log('Database error: ' . $e->getMessage());
                $saveErrorMessage = 'Unable to save set.';
            }
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
        <title>GymLog Edit Set</title>
    </head>
    <body>
        <h1>Edit set</h1>

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
                        $set['exercise_name'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></h2>

            <p><?= htmlspecialchars(
                        $set['workout_name'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>

            <p><?= htmlspecialchars(
                        $set['workout_date'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                ) ?></p>

            <p>Set <?= (int)$set['set_number'] ?></p>

            <?php
            if ($saveErrorMessage !== null): ?>
                <p><?= htmlspecialchars(
                            $saveErrorMessage,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                    ) ?></p>
            <?php
            endif; ?>

            <form action="set-edit.php?id=<?= $setId ?>" method="post">
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

                <button type="submit">Save changes</button>
            </form>

            <a href="workout.php?id=<?= (int)$set['workout_id'] ?>">Cancel</a>
        <?php
        endif; ?>

        <a href="index.php">Back to workouts</a>
    </body>
</html>