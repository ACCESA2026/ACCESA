<?php
session_start();
require_once __DIR__ . '/../Login/conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $matricula = trim($_GET['matricula'] ?? '');

  if ($matricula === '') {
    echo json_encode(['ok' => false, 'error' => 'Matricula vacia']);
    exit;
  }

  $sql = "
    SELECT 
      CONCAT(n.DsNombre,' ',ap.DsApellido,' ',am.DsApellido) AS nombre
    FROM mcredencial c
    JOIN cnombre n    ON n.CvNombre = c.CvNombre
    JOIN capellido ap ON ap.CvApellido = c.CvApePat
    JOIN capellido am ON am.CvApellido = c.CvApeMat
    WHERE c.Matricula = ?
    LIMIT 1
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute([$matricula]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    echo json_encode(['ok' => true, 'nombre' => $row['nombre']]);
  } else {
    echo json_encode(['ok' => false, 'error' => 'No encontrado']);
  }

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
