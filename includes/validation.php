<?php

/**
 * Request validation rules as pure functions.
 *
 * Controllers stay thin: they pull values out of the request, call these
 * functions and render errors. Keeping the rules here means the tests can
 * cover them without HTTP or a database.
 */

/**
 * Read a value out of a form as a string. Non-string input (arrays, for
 * example) becomes an empty string instead of crashing trim().
 */
function form_string(mixed $value, bool $trim = true): string
{
    if (!is_string($value)) {
        return '';
    }

    return $trim ? trim($value) : $value;
}

/** @return list<string> */
function registration_errors(string $username, string $email, string $password, string $confirmPassword): array
{
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username must be at most 50 characters';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email must be at most 100 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    } elseif (strlen($password) > 72) {
        $errors[] = 'Password must be at most 72 characters';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }

    return $errors;
}

/** @return list<string> */
function login_errors(string $username, string $password): array
{
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username is required';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    return $errors;
}

/** @return list<string> */
function task_errors(string $title, string $description): array
{
    $errors = [];

    if (empty($title)) {
        $errors[] = 'Title is required';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Title must be at most 255 characters';
    }

    if (strlen($description) > 65535) {
        $errors[] = 'Description must be at most 65535 characters';
    }

    return $errors;
}

/** Invalid values fall back to 'medium', matching the original behaviour. */
function normalize_priority(mixed $priority): string
{
    return in_array($priority, ['low', 'medium', 'high'], true) ? $priority : 'medium';
}

function valid_status(mixed $status): bool
{
    return in_array($status, ['pending', 'in_progress', 'completed'], true);
}

/** Returns the status filter to use, or null for "all tasks". */
function status_filter(mixed $status): ?string
{
    return valid_status($status) ? $status : null;
}

/** Returns a positive integer task id, or null for anything else. */
function task_id(mixed $id): ?int
{
    if (is_int($id)) {
        return $id >= 1 ? $id : null;
    }

    if (!is_string($id) || !ctype_digit($id)) {
        return null;
    }

    $value = (int) $id;

    return $value >= 1 ? $value : null;
}
