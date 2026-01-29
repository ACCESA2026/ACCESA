<?php
require_once "../Login/conexion.php";

$matricula = $_GET['matricula'] ?? null;
$fi = $_GET['fi'] ?? null;
$ff = $_GET['ff'] ?? null;

if (!$matricula || !$fi || !$ff) {
    echo json_encode(["faltas" => 0]);
    exit;
}

$sql = "SELECT COUNT(*) AS faltas 
        FROM cfaltas 
        WHERE Matricula = ?
        AND Fecha BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$matricula, $fi, $ff]);
$datos = $stmt->fetch();

echo json_encode($datos);
?>
