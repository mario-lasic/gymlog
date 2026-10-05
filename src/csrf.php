<?php

function csrfToken(): string
{
    $token = $_SESSION['csrf_token'] ?? null;

    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
    }

    return $token;
}

function requireValidCsrfToken(mixed $submittedToken): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? null;

    if (
        !is_string($sessionToken)
        || $sessionToken === ''
        || !is_string($submittedToken)
        || $submittedToken === ''
        || !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(403);
        echo 'Invalid form submission.';
        exit;
    }
}
