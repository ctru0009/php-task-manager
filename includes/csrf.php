<?php

/**
 * CSRF protection for state-changing requests.
 *
 * The token lives in the session and csrf_valid() compares it with
 * hash_equals(), which takes the same time for every input and therefore
 * leaks nothing about the expected value.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Hidden input every POST form includes. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_valid(mixed $token): bool
{
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/** Rejects the request unless the submitted token matches the session. */
function csrf_require(): void
{
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        require __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

/** Issues a fresh token, for example after signing in. */
function csrf_rotate(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
