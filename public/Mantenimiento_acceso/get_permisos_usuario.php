<?php
$BASE = '/' . explode('/', trim($_SERVER['SCRIPT_NAME'] ?? '', '/'))[0]; // Ej: /Mantenimiento_acceso
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',         // 🔑 Esto permite que la cookie sea global
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../Login/conexion.php';

// ✅ Permitir Administrador y Desarrollador
if (empty($_SESSION['usuario'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Sesión no válida']);
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo json_encode([]);
  exit;
}

try {
  // 🔧 CORREGIDO: tabla real es maccesos
  $sql = "SELECT modulo FROM maccesos WHERE CvUsuario = ? AND tiene_acceso = 1";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$id]);

  $modulos = $stmt->fetchAll(PDO::FETCH_COLUMN);
  echo json_encode($modulos);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
