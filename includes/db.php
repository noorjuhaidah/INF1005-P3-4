<?php
// includes/db.php
// Creates a PDO connection object: $pdo
// Every page that touches the database starts with:
//   require_once __DIR__ . '/../includes/db.php';
// This file pulls in config.php automatically.

require_once __DIR__ . '/config.php';

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_NAME,
    DB_CHARSET
);

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on SQL errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // fetch() returns associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Real prepared statements
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    die('<p style="text-align:center;margin-top:3rem;color:#c0392b;">
            Database connection failed. Please try again later.
         </p>');
}
