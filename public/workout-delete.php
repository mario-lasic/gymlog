<?php

require_once __DIR__ . '/../src/bootstrap.php';

$errorMessage = null;
$workout = null;
$deleteErrorMessage = null;

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
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage());
        $errorMessage = 'Unable to load workout.';
    }
}

if ($errorMessage === null) {
    $name = $workout['name'];
    $date = $workout['workout_date'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireValidCsrfToken($_POST['csrf_token'] ?? null);

        try {
            $sql = "DELETE FROM workouts WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                    'id' => $id
            ]);
            header('Location: /', true, 303);
            exit();
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage());
            $deleteErrorMessage = 'Unable to delete workout.';
        }
    }
}
$csrfToken = csrfToken();


$pageTitle = 'Delete workout';
require __DIR__ . '/../templates/header.php';
?>
        <h1>Delete workout</h1>
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
            <form action="workout-delete.php?id=<?= $id ?>" method="post">
                <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken) ?>"
                >

                <div class="input-container">
                    <p><?= e($date) ?></p>
                </div>

                <div class="input-container">
                    <p><?= e($name) ?></p>
                </div>
                <p>This will permanently delete this workout, its exercise entries, and all recorded sets. Exercises in
                    the
                    catalog will remain.</p>
                <button type="submit">Delete workout</button>
            </form>
            <a href="workout.php?id=<?= $id ?>">Cancel</a>
        <?php
        endif; ?>
        <a href="index.php">Back to workouts</a>
<?php require __DIR__ . '/../templates/footer.php'; ?>
