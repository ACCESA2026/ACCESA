<?php
session_start();
require_once '../Login/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['ok' => false, 'msg' => 'Método inválido']);
  exit;
}

$apertura = $_POST['apertura'] ?? null;
$cierre   = $_POST['cierre'] ?? null;

if (!$apertura || !$cierre) {
  echo json_encode(['ok' => false, 'msg' => 'Horas incompletas']);
  exit;
}

// Desactivar anteriores
$conn->query("UPDATE cconfig_asistencia SET Activo = 0");

// Insertar nuevo
$stmt = $conn->prepare("
  INSERT INTO cconfig_asistencia (HoraApertura, HoraCierre, Activo)
  VALUES (?, ?, 1)
");
$stmt->execute([$apertura, $cierre]);

echo json_encode([
  'ok' => true,
  'msg' => 'Horario guardado correctamente'
]);
