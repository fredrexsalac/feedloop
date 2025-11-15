<?php
$host = 'db.fr-pari1.bengt.wasmernet.com';
$port = '10272'; // include port if not default 3306
$db   = 'feedloop';
$user = '87bf77927fb38000e7d770499d79';
$pass = '069187bf-7793-728f-8000-612369950f59';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database configuration failed: " . $e->getMessage());
}
?>
