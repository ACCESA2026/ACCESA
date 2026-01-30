<?php
require_once "../Login/conexion.php";
header('Content-Type: application/json; charset=UTF-8');

$matricula = $_GET['matricula'] ?? '';

if (empty($matricula)) {
  echo json_encode(['error' => 'Matrícula vacía']);
  exit;
}

$stmt = $conn->prepare("
  SELECT 
    m.Matricula,
    n.DsNombre AS nombre,
    ap.DsApellido AS apep,
    am.DsApellido AS apem,
    m.CURP AS curp,
    g.DsGenero AS genero,
    m.Nacionalidad AS nacionalidad,
    m.Telefono AS telefono,
    m.Correo AS correo,
    e.DsEspecialidad AS carrera,
    s.DsSemestre AS semestre,
    m.Grupo AS grupo,
    t.DsTurno AS turno,
    m.Estatus AS estatus,
    m.Pais AS pais,
    m.EstadoCivil AS estado_civil,
    m.FechaNacimiento AS nacimiento,
    m.Edad AS edad
  FROM mMatricula m
  LEFT JOIN cNombre n ON m.CvNombre = n.CvNombre
  LEFT JOIN cApellido ap ON m.CvApePat = ap.CvApellido
  LEFT JOIN cApellido am ON m.CvApeMat = am.CvApellido
  LEFT JOIN cGenero g ON m.CvGenero = g.CvGenero
  LEFT JOIN mEspecialidad e ON m.CvEspecialidad = e.CvEspecialidad
  LEFT JOIN mSemestre s ON m.CvSemestre = s.CvSemestre
  LEFT JOIN mTurno t ON m.CvTurno = t.CvTurno
  WHERE m.Matricula = ?
  LIMIT 1
");
$stmt->execute([$matricula]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo json_encode($row);
} else {
  echo json_encode(['notfound' => true]);
}
?>
