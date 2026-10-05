<?php

require_once __DIR__ . '/../src/bootstrap.php';

$name = '';
$errors = [];
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? null);

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


$pageTitle = 'GymLog New Exercise';
require __DIR__ . '/../templates/header.php';
?>
        <h1>Create exercise</h1>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= e($errorMessage) ?></p>
        <?php
        endif; ?>
        <form action="exercise-create.php" method="post">
            <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
            >
            <div class="input-container">
                <label for="name">Name</label>
                <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($name) ?>"
                        maxlength="100"
                        required
                >
                <?php
                if (isset($errors['name'])): ?>
                    <p><?= e($errors['name']) ?></p>
                <?php
                endif; ?>
            </div>
            <button type="submit">Create Exercise</button>
        </form>
        <a href="exercises.php">Back to exercises</a>
<?php require __DIR__ . '/../templates/footer.php'; ?>
