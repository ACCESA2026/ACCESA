<?php
// /Mantenimiento_acceso/guardarPermisosAjax.php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../Login/conexion.php';

/* ==========================
   VALIDAR SESIÓN
========================== */
if (empty($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sesión no válida']);
    exit;
}

/* ==========================
   VALIDAR ROL (ADMIN / DEV)
   Admin = 4
   Desarrollador = 5
========================== */
$rolId = (int)($_SESSION['usuario']['CvTipPersona'] ?? 0);

if (!in_array($rolId, [4, 5], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

/* ==========================
   LEER DATOS
========================== */
$data = json_decode(file_get_contents('php://input'), true);

$usuarioId = (int)($data['usuarioId'] ?? 0);
$permisos  = $data['permisos'] ?? [];

if ($usuarioId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario no válido']);
    exit;
}

/* ==========================
   GUARDAR PERMISOS
========================== */
try {
    $conn->beginTransaction();

    $stmtDel = $conn->prepare("DELETE FROM maccesos WHERE CvUsuario = ?");
    $stmtDel->execute([$usuarioId]);

    if (!empty($permisos)) {
        $stmtIns = $conn->prepare(
            "INSERT INTO maccesos (CvUsuario, modulo, tiene_acceso)
             VALUES (?, ?, 1)"
        );

        foreach ($permisos as $modulo) {
            $stmtIns->execute([$usuarioId, $modulo]);
        }
    }

    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
