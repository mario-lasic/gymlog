<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$name = '';
$errors = [];
$errorMessage = null;

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

    $rawName = $_POST['name'] ?? null;

    if (!is_string($rawName)) {
        $errors['name'] = 'Enter a valid name.';
    } else {
        $name = trim($rawName);
        $nameLength = mb_strlen($name, 'UTF-8');

        if ($nameLength < 1 || $nameLength > 100) {
            $errors['name'] = 'Name must contain between 1 and 100 characters.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo = require __DIR__ . '/../src/database.php';
            $sql = "INSERT INTO exercises(name) VALUES (:name)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                    ':name' => $name,
            ]);
            header('Location: /exercises.php', true, 303);
            exit();
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                $errors['name'] = 'An exercise with this name already exists.';
            } else {
                http_response_code(500);
                error_log('Database error: ' . $e->getMessage());
                $errorMessage = 'Unable to save exercise.';
            }
        }
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
        <title>GymLog New Exercise</title>
    </head>
    <body>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        endif; ?>
        <form action="exercise-create.php" method="post">
            <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            >
            <div class="input-container">
                <label for="name">Name</label>
                <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        maxlength="100"
                        required
                >
                <?php
                if (isset($errors['name'])): ?>
                    <p><?= htmlspecialchars($errors['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                <?php
                endif; ?>
            </div>
            <button type="submit">Create Exercise</button>
        </form>
        <a href="exercises.php">Back to exercises</a>
    </body>
</html>
