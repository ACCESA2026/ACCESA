<?php
session_start();
date_default_timezone_set('America/Mexico_City');

require_once '../Login/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método inválido']);
    exit;
}

$matricula = $_POST['matricula'] ?? null;
$hora      = $_POST['hora'] ?? null;
$fecha     = $_POST['fecha'] ?? date('Y-m-d'); // ⬅️ FECHA QUE VIENE DESDE JS O LA ACTUAL

if (!$matricula || !$hora) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}
// ================= VALIDAR HORARIO GENERAL =================
$conf = $conn->query("
  SELECT HoraApertura, HoraCierre 
  FROM cconfig_asistencia 
  WHERE Activo = 1 
  LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$conf) {
    echo json_encode(['error' => 'Horario de asistencia no configurado']);
    exit;
}

$horaActual = date("H:i:s");

if ($horaActual < $conf['HoraApertura'] || $horaActual > $conf['HoraCierre']) {
    echo json_encode(['error' => 'Fuera del horario de asistencia']);
    exit;
}
// ==========================================================
// Obtener datos reales desde mcredencial
$sql = "SELECT CvNombre, CvApePat, CvApeMat, CvSemestre 
        FROM mcredencial 
        WHERE Matricula = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$matricula]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo json_encode(['error' => 'Alumno no encontrado']);
    exit;
}

$CvNombre   = $alumno['CvNombre'];
$CvApePat   = $alumno['CvApePat'];
$CvApeMat   = $alumno['CvApeMat'];
$CvSemestre = $alumno['CvSemestre'];

// INSERTAR TAMBIÉN LA FECHA ⬇️
$sqlInsert = "INSERT INTO casistencia 
              (Matricula, CvNombre, CvApePat, CvApeMat, CvSemestre, Fecha, Hora)
              VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sqlInsert);

$ok = $stmt->execute([
    $matricula,
    $CvNombre,
    $CvApePat,
    $CvApeMat,
    $CvSemestre,
    $fecha,   // ⬅️ FECHA INSERTADA CORRECTAMENTE
    $hora
]);

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Error al insertar en BD']);
}
?>
