<?php
header("Content-Type: application/json; charset=utf-8");
require_once "../Login/conexion.php";

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
  echo json_encode([]);
  exit;
}

$sql = $conn->prepare("
  SELECT 
    mc.Matricula,
    CONCAT(n.DsNombre,' ', ap1.DsApellido,' ', ap2.DsApellido) AS nombre
  FROM mcredencial mc
  LEFT JOIN cnombre n   ON mc.CvNombre  = n.CvNombre
  LEFT JOIN capellido ap1 ON mc.CvApePat = ap1.CvApellido
  LEFT JOIN capellido ap2 ON mc.CvApeMat = ap2.CvApellido
  WHERE mc.Matricula LIKE ?
  ORDER BY mc.Matricula
  LIMIT 10
");

$sql->execute([$q . '%']);
$res = $sql->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($res, JSON_UNESCAPED_UNICODE);
