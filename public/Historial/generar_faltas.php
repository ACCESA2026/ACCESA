<?php
date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . '/../Login/conexion.php';

$hoy = date('Y-m-d');
$horaActual = date('H:i:s');

/* 1️⃣ Obtener horario */
$conf = $conn->query("
  SELECT HoraCierre 
  FROM cconfig_asistencia 
  WHERE Activo = 1 
  LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$conf) {
  echo "No hay horario activo";
  exit;
}

// Si aún no es hora de cierre
if ($horaActual <= $conf['HoraCierre']) {
  echo "Aún no es hora de generar faltas";
  exit;
}

/* 2️⃣ Obtener TODOS los alumnos */
$alumnos = $conn
  ->query("SELECT Matricula FROM mcredencial")
  ->fetchAll(PDO::FETCH_COLUMN);

if (!$alumnos) {
  echo "No hay alumnos";
  exit;
}

/* 3️⃣ Obtener alumnos con asistencia HOY */
$asistieron = $conn->prepare("
  SELECT DISTINCT Matricula 
  FROM casistencia 
  WHERE Fecha = ?
");
$asistieron->execute([$hoy]);
$presentes = array_flip($asistieron->fetchAll(PDO::FETCH_COLUMN));

/* 4️⃣ Insertar o actualizar faltas */
$contador = 0;

foreach ($alumnos as $mat) {
  if (isset($presentes[$mat])) continue;

  $stmt = $conn->prepare("
    SELECT Faltas 
    FROM cfaltas 
    WHERE Matricula = ?
  ");
  $stmt->execute([$mat]);
  $existe = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($existe) {
    $conn->prepare("
      UPDATE cfaltas 
      SET Faltas = Faltas + 1,
          UltimaFecha = ?
      WHERE Matricula = ?
    ")->execute([$hoy, $mat]);
  } else {
    $conn->prepare("
      INSERT INTO cfaltas 
      (Matricula, Faltas, FechaUltimaFalta, UltimaFecha)
      VALUES (?, 1, ?, ?)
    ")->execute([$mat, $hoy, $hoy]);
  }

  $contador++;
}

echo "Proceso finalizado. Faltas generadas: $contador";
