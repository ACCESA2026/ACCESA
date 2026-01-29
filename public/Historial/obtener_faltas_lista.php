<?php
require_once "../Login/conexion.php";

$fi = $_GET['fi'] ?? null;
$ff = $_GET['ff'] ?? null;
$grupo = $_GET['grupo'] ?? "";
$plan = $_GET['plan'] ?? "";
$faltasFiltro = $_GET['faltasFiltro'] ?? "";

$sql = "
SELECT 
    f.Fecha,
    f.Matricula,
    cn.DsNombre AS Nombre,
    gs.DsGrupo AS Grupo,
    sm.DsSemestre AS Semestre,
    'Falta' AS Tipo,
    1 AS Faltas
FROM cfaltas f
JOIN mcredencial mc ON f.Matricula = mc.Matricula
LEFT JOIN cnombre cn ON mc.CvNombre = cn.CvNombre
LEFT JOIN msemestre sm ON mc.CvSemestre = sm.CvSemestre
LEFT JOIN mgrupo gs ON mc.CvGrupo = gs.CvGrupo
WHERE f.Fecha BETWEEN ? AND ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$fi, $ff]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
