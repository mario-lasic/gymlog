<?php

require_once __DIR__ . '/../src/bootstrap.php';

$set = null;
$errorMessage = null;
$deleteErrorMessage = null;

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

if ($errorMessage === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? null);

    try {
        $stmt = $pdo->prepare('DELETE FROM sets WHERE id = :id');
        $stmt->execute([':id' => $setId]);

        header(
            'Location: workout.php?id=' . (int) $set['workout_id'],
            true,
            303
        );
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage());
        $deleteErrorMessage = 'Unable to delete set.';
    }
}

$csrfToken = csrfToken();

$pageTitle = 'GymLog Delete Set';
require __DIR__ . '/../templates/header.php';
?>
        <h1>Delete set</h1>

        <?php if ($errorMessage !== null): ?>
            <p><?= e($errorMessage) ?></p>
        <?php else: ?>
            <h2><?= e($set['exercise_name']) ?></h2>

            <p><?= e($set['workout_name']) ?></p>

            <p><?= e($set['workout_date']) ?></p>

            <p>
                Set <?= (int) $set['set_number'] ?>:
                <?= (int) $set['reps'] ?> reps ×
                <?= e($set['weight_kg']) ?> kg
            </p>

            <p>This will permanently delete only this set.</p>

            <?php if ($deleteErrorMessage !== null): ?>
                <p><?= e($deleteErrorMessage) ?></p>
            <?php endif; ?>

            <form action="set-delete.php?id=<?= $setId ?>" method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >

                <button type="submit">Delete set</button>
            </form>

            <a href="workout.php?id=<?= (int) $set['workout_id'] ?>">Cancel</a>
        <?php endif; ?>

        <a href="index.php">Back to workouts</a>
<?php require __DIR__ . '/../templates/footer.php'; ?>
