<?php

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
        $csrfToken = $_POST['csrf_token'] ?? null;

        if (
                !is_string($csrfToken)
                || !hash_equals($_SESSION['csrf_token'], $csrfToken)
        ) {
            http_response_code(403);
            echo 'Invalid form submission.';
            exit;
        }

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
$csrfToken = $_SESSION['csrf_token'];

?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
                content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Delete workout</title>
    </head>
    <body>
        <h1>Delete workout</h1>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        else: ?>
            <?php
            if ($deleteErrorMessage !== null): ?>
                <p><?= htmlspecialchars($deleteErrorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php
            endif; ?>
            <form action="workout-delete.php?id=<?= $id ?>" method="post">
                <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                >

                <div class="input-container">
                    <p><?= htmlspecialchars($date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                </div>

                <div class="input-container">
                    <p><?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
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
    </body>
</html>
