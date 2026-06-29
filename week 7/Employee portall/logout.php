<?php
declare(strict_types=1);

/**
 * Employee Portal Logout
 *
 * Clears the active session and redirects the employee back to the login page.
 */

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookieParameters = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $cookieParameters['path'],
        $cookieParameters['domain'],
        $cookieParameters['secure'],
        $cookieParameters['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;
