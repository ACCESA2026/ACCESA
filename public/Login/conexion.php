<?php
// /Login/conexion.php

$host = 'ballast.proxy.rlwy.net';
$dbname = 'bd_conalep';
$username = 'root';
$password = 'OYcxkaLpHbWOLCQdlRxQhTwFNbbnkDVH';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // En producción, no expongas el error real
    die('Error de conexión a la base de datos.');
}
