<?php
// Login/conexion.php

$host = 'ballast.proxy.rlwy.net';
$port = '50720';
$dbname = 'railway';
$username = 'root';
$password = 'OYcxkaLpHbWOLCQdlRxQhTwFNbbnkDVH';

// Activa esto en local si quieres ver el error exacto
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
} catch (PDOException $e) {
    if ($debug) {
        // Modo debug (descomentar si estás en desarrollo)
        die('Error de conexión: ' . $e->getMessage());
    } else {
        // Modo producción
        die('Error de conexión a la base de datos.');
    }
}
