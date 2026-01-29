<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Login/conexion.php';

// Cambié $conexion por $conn para que coincida con tu archivo conexion.php
if (!isset($conn)) {
    http_response_code(500);
    echo json_encode(['error' => 'Conexión no inicializada']);
    exit;
}

// Validar que el usuario está logueado
if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

try {
    $sql = "
        SELECT 
            u.CvUsuario,
            u.NombreCompleto,
            t.DsTipPersona AS rol
        FROM musuarios u
        INNER JOIN ctippersona t 
            ON u.CvTipPersona = t.CvTipPersona
    ";

    // Usamos $conn, que es la variable de conexión correcta
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
