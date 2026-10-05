<?php

function validateSet(array $input): array
{
    $errors = [];
    $reps = '';
    $repsValue = false;
    $weightKg = '';

    $rawReps = $input['reps'] ?? null;

    if (!is_string($rawReps)) {
        $errors['reps'] = 'Enter valid reps.';
    } else {
        $reps = trim($rawReps);

        $repsValue = filter_var($reps, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => 65535,
            ],
        ]);

        if ($repsValue === false) {
            $errors['reps'] =
                'Reps must be a whole number between 1 and 65535.';
        }
    }

    $rawWeightKg = $input['weight_kg'] ?? null;

    if (!is_string($rawWeightKg)) {
        $errors['weight_kg'] = 'Enter a valid weight.';
    } else {
        $weightKg = trim($rawWeightKg);

        if (
            preg_match(
                '/\A[0-9]{1,4}(?:\.[0-9]{1,2})?\z/',
                $weightKg
            ) !== 1
        ) {
            $errors['weight_kg'] =
                'Weight must be between 0 and 9999.99 kg, '
                . 'with at most two decimal places.';
        }
    }

    return [
        'reps' => $reps,
        'reps_value' => $repsValue,
        'weight_kg' => $weightKg,
        'errors' => $errors,
    ];
}