<?php
// Login/conexion.php

$host = 'ballast.proxy.rlwy.net';
$port = '50720';
$dbname = 'railway';
$username = 'root';
$password = 'OYcxkaLpHbWOLCQdlRxQhTwFNbbnkDVH';

$debug = false;

try {
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Exponer $conn como variable global para otros scripts
    $GLOBALS['conn'] = $conn;

} catch (PDOException $e) {
    if ($debug) {
        die('Error: ' . $e->getMessage());
    } else {
        die('Error de conexión a la base de datos.');
    }
}
