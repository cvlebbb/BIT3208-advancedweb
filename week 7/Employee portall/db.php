<?php
declare(strict_types=1);

/**
 * Database connection bootstrap for the Employee Portal.
 *
 * This file creates and returns one secure PDO connection configured for
 * MySQL, UTF-8, exceptions, and associative fetches. It is intentionally
 * small so every page can include it and share the same database settings.
 */

// XAMPP default database credentials.
$databaseHost = 'localhost';
$databaseName = 'employee_portal';
$databaseUsername = 'root';
$databasePassword = '';

// utf8mb4 safely supports the full Unicode range, including extended symbols.
$databaseCharset = 'utf8mb4';

// Data Source Name used by PDO to connect to the MySQL database.
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $databaseHost,
    $databaseName,
    $databaseCharset
);

try {
    /**
     * PDO options harden the connection and make failures explicit:
     * - ERRMODE_EXCEPTION throws exceptions instead of hiding SQL errors.
     * - FETCH_ASSOC returns rows as readable associative arrays.
     * - EMULATE_PREPARES disabled asks MySQL to use native prepared statements.
     */
    $pdo = new PDO($dsn, $databaseUsername, $databasePassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
} catch (PDOException $exception) {
    /**
     * Never expose raw database credentials or connection internals to users.
     * The detailed message is logged for administrators, while the browser
     * receives a generic production-safe error.
     */
    error_log('Employee Portal database connection failed: ' . $exception->getMessage());

    http_response_code(500);
    exit('A database connection error occurred. Please contact the system administrator.');
}
