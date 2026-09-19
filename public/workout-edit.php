<?php

session_start();
date_default_timezone_set('Europe/Zagreb');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$workout = null;
$errorMessage = null;
$saveErrorMessage = null;
$errors = [];

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
    $date = $workout['workout_date'];
    $name = $workout['name'];
    $note = $workout['note'] ?? '';


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

        $rawNote = $_POST['note'] ?? '';

        if (!is_string($rawNote)) {
            $errors['note'] = 'Enter a valid note.';
        } else {
            $note = trim($rawNote);

            if (mb_strlen($note, 'UTF-8') > 5000) {
                $errors['note'] = 'Note must contain at most 5000 characters.';
            }
        }

        $rawDate = $_POST['date'] ?? null;

        if (!is_string($rawDate) || $rawDate === '') {
            $date = '';
            $errors['date'] = 'Enter a date.';
        } else {
            $date = $rawDate;

            if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $date) !== 1) {
                $errors['date'] = 'Date is in wrong format, use YYYY-MM-DD.';
            } else {
                $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

                if ($dateObject === false || $dateObject->format('Y-m-d') !== $date) {
                    $errors['date'] = 'Enter a valid calendar date.';
                } else {
                    $today = new DateTimeImmutable('today');
                    $minimumDate = new DateTimeImmutable('1000-01-01');

                    if ($dateObject < $minimumDate) {
                        $errors['date'] = 'Date must be on or after 1000-01-01.';
                    } elseif ($dateObject > $today) {
                        $errors['date'] = 'Workout date cannot be in the future.';
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                $sql = "UPDATE workouts SET workout_date=:workout_date, name=:name, note=:note WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                        ':workout_date' => $date,
                        ':name' => $name,
                        ':note' => $note === '' ? null : $note,
                        ':id' => $id,
                ]);
                header("Location: workout.php?id=$id", true, 303);
                exit();
            } catch (PDOException $e) {
                http_response_code(500);
                error_log('Database error: ' . $e->getMessage());
                $saveErrorMessage = 'Unable to save workout.';
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
        <title>GymLog Edit Workout</title>
    </head>
    <body>
        <?php
        if ($errorMessage !== null): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        else: ?>
            <?php
            if ($saveErrorMessage !== null): ?>
                <p><?= htmlspecialchars($saveErrorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php
            endif; ?>
            <form action="workout-edit.php?id=<?= $workout['id'] ?>" method="post">
                <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                >

                <div class="input-container">
                    <label for="date">Date</label>
                    <input
                            type="date"
                            id="date"
                            name="date"
                            value="<?= htmlspecialchars(
                                    $date,
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                            ) ?>"
                            required
                    >

                    <?php
                    if (isset($errors['date'])): ?>
                        <p><?= htmlspecialchars($errors['date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                    <?php
                    endif; ?>
                </div>

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

                <div class="input-container">
                    <label for="note">Note</label>
                    <textarea
                            id="note"
                            name="note"
                            cols="30"
                            rows="10"
                            maxlength="5000"
                    ><?= htmlspecialchars($note ?? "", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>

                    <?php
                    if (isset($errors['note'])): ?>
                        <p><?= htmlspecialchars($errors['note'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                    <?php
                    endif; ?>
                </div>

                <button type="submit">Save changes</button>
            </form>
            <a href="workout.php?id=<?= $workout['id'] ?>">Cancel</a>
        <?php
        endif; ?>
        <a href="index.php">Back to workouts</a>
    </body>
</html>
