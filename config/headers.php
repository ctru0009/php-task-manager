<?php

/**
 * Security headers sent with every response.
 *
 * Cache directives are not set here: PHP's session cache limiter already sends
 * "Cache-Control: no-store, no-cache, must-revalidate" on every request that
 * starts a session, and all pages in this application do.
 */
header('X-Frame-Options: DENY');
header("Content-Security-Policy: frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
