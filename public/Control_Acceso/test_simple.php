<?php
header('Content-Type: application/json');
require_once "../Login/conexion.php";

$matricula = "210700280-6";

$sql = "SELECT matricula FROM mcredencial WHERE matricula = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matricula);
$stmt->execute();
$stmt->bind_result($m);

if ($stmt->fetch()) {
  echo json_encode(["ok" => true, "matricula" => $m]);
} else {
  echo json_encode(["ok" => false]);
}
