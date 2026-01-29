<?php
file_put_contents("debug_log.txt", print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents("debug_files.txt", print_r($_FILES, true) . "\n", FILE_APPEND);

    session_start();
require_once __DIR__ . '/../Login/conexion.php';

if (empty($_SESSION['usuario'])) {
  header('Location: ../Login/login.php');
  exit;
}

// ================= DATOS =================
$matricula       = trim($_POST['matricula'] ?? '');
$tipoReporte     = trim($_POST['tipoReporte'] ?? '');
$descripcion     = trim($_POST['descripcion'] ?? '');
$fechaIncidente  = $_POST['fechaIncidente'] ?? date('Y-m-d');

// Usuario que reporta (compatible con tus 2 posibles nombres)
$usuarioId = $_SESSION['usuario']['id'] ?? $_SESSION['usuario']['CvUsuario'] ?? 0;
$rol       = $_SESSION['usuario']['rol'] ?? $_SESSION['usuario']['DsTipPersona'] ?? 'Desconocido';

// ================= VALIDACIONES =================
if (!$matricula || !$tipoReporte || !$descripcion) {
  die('Datos incompletos');
}

if (!$usuarioId) {
  die('Sesión inválida: usuario no identificado');
}

// ================= OBTENER NOMBRE DESDE BD (IMPORTANTE) =================
$sqlNombre = "
  SELECT 
    CONCAT(n.DsNombre,' ',ap.DsApellido,' ',am.DsApellido) AS nombre
  FROM mcredencial c
  JOIN cnombre n    ON n.CvNombre = c.CvNombre
  JOIN capellido ap ON ap.CvApellido = c.CvApePat
  JOIN capellido am ON am.CvApellido = c.CvApeMat
  WHERE c.Matricula = ?
  LIMIT 1
";
$stmtN = $conn->prepare($sqlNombre);
$stmtN->execute([$matricula]);
$rowN = $stmtN->fetch(PDO::FETCH_ASSOC);

if (!$rowN) {
  die('Matrícula no encontrada');
}

$nombreAlumno = $rowN['nombre'];

// ================= EVIDENCIA (OPCIONAL) =================
$nombreArchivo = null;

if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {

  if ($_FILES['evidencia']['error'] !== UPLOAD_ERR_OK) {
    die('Error al subir archivo');
  }

  $ext = strtolower(pathinfo($_FILES['evidencia']['name'], PATHINFO_EXTENSION));
  $permitidas = ['jpg','jpeg','png','pdf'];

  if (!in_array($ext, $permitidas, true)) {
    die('Formato de evidencia no permitido');
  }

  $carpeta = __DIR__ . '/evidencias_reportes/';
  if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
  }

  $nombreArchivo = 'reporte_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

  if (!move_uploaded_file($_FILES['evidencia']['tmp_name'], $carpeta . $nombreArchivo)) {
    die('No se pudo guardar la evidencia');
  }
}

// ================= INSERT =================
try {

  $sql = "
    INSERT INTO rreportesalumnos
    (Matricula, NombreAlumno, TipoReporte, Descripcion,
     FechaIncidente, Evidencia, UsuarioReporta, RolReporta, Estado)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute([
    $matricula,
    $nombreAlumno,
    $tipoReporte,
    $descripcion,
    $fechaIncidente,
    $nombreArchivo,
    $usuarioId,
    $rol
  ]);

} catch (Throwable $e) {
  file_put_contents(__DIR__ . "/debug_sql_error.txt", $e->getMessage() . PHP_EOL, FILE_APPEND);
  die("Error al guardar reporte.");
}

// ================= REDIRIGIR =================
header('Location: reportes_alumnos.php?reporte=ok');
exit;
