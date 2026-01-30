<?php
ob_start();
session_start();

if (empty($_SESSION['usuario'])){
  header('Location: ../Login/login.php');
  exit;
}

require_once '../Login/conexion.php';
/* ================================
   CONSULTA PRINCIPAL DEL HISTORIAL
================================== */

$sql = "
SELECT 
    ca.Fecha,
    ca.Hora,
    ca.Matricula,
    cn.DsNombre AS Nombre,
    gs.DsGrupo AS Grupo,
    sm.DsSemestre AS Semestre,
    'Asistencia' AS Tipo,
    0 AS Faltas
FROM casistencia ca
JOIN cnombre cn ON ca.CvNombre = cn.CvNombre
JOIN msemestre sm ON ca.CvSemestre = sm.CvSemestre
JOIN mcredencial mc ON ca.Matricula = mc.Matricula
JOIN mgrupo gs ON mc.CvGrupo = gs.CvGrupo
ORDER BY ca.Fecha DESC, ca.Hora DESC
";

$stmt = $conn->query($sql);
$historial = $stmt->fetchAll();

/* ================================
   SELECTS
================================== */
$grupos = $conn->query("SELECT DsGrupo FROM mgrupo ORDER BY DsGrupo")->fetchAll();
$planes = $conn->query("SELECT DsPlan FROM mplanestudios ORDER BY DsPlan")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial de estudiantes</title>

<link rel="stylesheet" href="../Centro_logo/header.css">
<link rel="stylesheet" href="../Menu/menu.css">
<link rel="stylesheet" href="historial.css">
</head>

<body>

<?php include '../Centro_logo/header.php'; ?>

<!-- TODO tu HTML aquí -->

</body>
</html>
<?php ob_end_flush(); ?>
