<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Mexico_City');

$SECRET = '241001';
$key = $_GET['key'] ?? '';
if (!hash_equals($SECRET, (string)$key)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'message'=>'Forbidden']);
  exit;
}

require_once __DIR__ . '/../Login/conexion.php'; // debe dejar $conn como PDO

$fecha = $_GET['date'] ?? date('Y-m-d');
$force = isset($_GET['force']);

// opcional: no correr antes del cierre
$horaCierre = '21:10';
if (!$force && date('H:i') < $horaCierre) {
  echo json_encode(['ok'=>true,'skipped'=>true,'date'=>$fecha,'time'=>date('H:i')]);
  exit;
}

try {
  $conn->beginTransaction();

  /* 1) Insertar ausentes del día a detalle (anti-duplicado por UNIQUE Matricula+Fecha) */
  $sqlDet = "
    INSERT IGNORE INTO cfaltas_detalle (Matricula, Fecha)
    SELECT a.DsMatricula, :f
    FROM mmatricula a
    LEFT JOIN casistencia ca
      ON ca.Matricula = a.DsMatricula
     AND ca.Fecha = :f
    WHERE ca.Matricula IS NULL
  ";
  $stDet = $conn->prepare($sqlDet);
  $stDet->execute([':f' => $fecha]);
  $insertadosDetalle = $stDet->rowCount();

  /* 2) Sumar a cfaltas SOLO los que se insertaron hoy en detalle */
  $sqlUp = "
    INSERT INTO cfaltas (Matricula, Faltas, FechaUltimaFalta, FechaRegistro, UltimaFecha)
    SELECT d.Matricula, 1, d.Fecha, CURDATE(), NOW()
    FROM cfaltas_detalle d
    WHERE d.Fecha = :f
    ON DUPLICATE KEY UPDATE
      Faltas = Faltas + 1,
      FechaUltimaFalta = VALUES(FechaUltimaFalta),
      UltimaFecha = VALUES(UltimaFecha)
  ";
  $stUp = $conn->prepare($sqlUp);
  $stUp->execute([':f' => $fecha]);

  $conn->commit();

  echo json_encode([
    'ok' => true,
    'fecha' => $fecha,
    'detalle_insertados' => $insertadosDetalle,
    'cfaltas_actualizado' => true
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'Error','error'=>$e->getMessage()]);
}
