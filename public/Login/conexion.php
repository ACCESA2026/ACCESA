<?php
// Login/conexion.php

$host = 'ballast.proxy.rlwy.net';
$port = '50720';
$dbname = 'railway';
$username = 'root';
$password = 'OYcxkaLpHbWOLCQdlRxQhTwFNbbnkDVH';

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
} catch (PDOException $e) {
    // En producción, no mostrar el error directamente
    die('Error de conexión a la base de datos.');
    // Para debugging:
    // die('Error: ' . $e->getMessage());
}
