<?php

/**
 * Session hardening. Must be included before session_start().
 *
 * HttpOnly keeps JavaScript away from the session cookie, SameSite=Lax stops
 * the browser from sending it on cross-site form posts, and strict mode makes
 * PHP ignore a session id it did not create (session fixation).
 */
$secureCookie = filter_var(getenv('SESSION_COOKIE_SECURE') ?: '0', FILTER_VALIDATE_BOOL);

session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
]);

ini_set('session.use_strict_mode', '1');
