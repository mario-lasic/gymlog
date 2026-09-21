<?php

function validateWorkout(array $input): array
{
    $errors = [];
    $name = '';
    $note = '';
    $date = '';
    $rawName = $input['name'] ?? null;

    if (!is_string($rawName)) {
        $errors['name'] = 'Enter a valid name.';
    } else {
        $name = trim($rawName);
        $nameLength = mb_strlen($name, 'UTF-8');

        if ($nameLength < 1 || $nameLength > 100) {
            $errors['name'] = 'Name must contain between 1 and 100 characters.';
        }
    }

    $rawNote = $input['note'] ?? '';
    if (!is_string($rawNote)) {
        $errors['note'] = 'Enter a valid note.';
    } else {
        $note = trim($rawNote);

        if (mb_strlen($note, 'UTF-8') > 5000) {
            $errors['note'] = 'Note must contain at most 5000 characters.';
        }
    }

    $rawDate = $input['date'] ?? null;

    if (!is_string($rawDate) || $rawDate === '') {
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
    return [
        'date' => $date,
        'name' => $name,
        'note' => $note,
        'errors' => $errors,
    ];
}