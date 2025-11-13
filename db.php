<?php
$host = 'db.fr-pari1.bengt.wasmernet.com';
$port = '10272'; // include port if not default 3306
$db   = 'feedloop_db';
$user = '2c0c684b76ea80000e258d41570e';
$pass = '06912c0c-684b-78e3-8000-d52defb57ed3';
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
