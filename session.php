<?php
/**
 * StockSense - Secure Session Bootstrap
 * Include this at the very top of every page (before any output).
 */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    session_name('stocksense_sid');
    session_start();

    // Regenerate session id periodically to mitigate session fixation
    if (empty($_SESSION['last_regen'])) {
        $_SESSION['last_regen'] = time();
    } elseif (time() - $_SESSION['last_regen'] > 900) {
        session_regenerate_id(true);
        $_SESSION['last_regen'] = time();
    }
}
