<?php
// /Login/conexion.php

$host = 'sql111.infinityfree.com';
$dbname = 'if0_40692155_bd_conalep';
$username = 'if0_40692155';
$password = 'Cd241001';

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
