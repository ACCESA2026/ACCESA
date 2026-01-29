<?php
date_default_timezone_set('America/Mexico_City');
require_once '../Login/conexion.php';

$hoy = date('Y-m-d');
$horaActual = date('H:i:s');

/* 1️⃣ Obtener horario */
$conf = $conn->query("
  SELECT HoraCierre 
  FROM cconfig_asistencia 
  WHERE Activo = 1 
  LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$conf) return;

// Si aún no es hora de cierre, no hacer nada
if ($horaActual <= $conf['HoraCierre']) return;

/* 2️⃣ Obtener TODOS los alumnos */
$alumnos = $conn->query("SELECT Matricula FROM mcredencial")->fetchAll(PDO::FETCH_COLUMN);

/* 3️⃣ Obtener alumnos con asistencia HOY */
$asistieron = $conn->prepare("
  SELECT DISTINCT Matricula 
  FROM casistencia 
  WHERE Fecha = ?
");
$asistieron->execute([$hoy]);
$presentes = array_flip($asistieron->fetchAll(PDO::FETCH_COLUMN));

/* 4️⃣ Insertar o actualizar faltas */
foreach ($alumnos as $mat) {
    if (isset($presentes[$mat])) continue; // Alumno asistió

    // Verificar si ya tiene registro en cfaltas
    $stmt = $conn->prepare("SELECT Faltas FROM cfaltas WHERE Matricula = ?");
    $stmt->execute([$mat]);
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        // Alumno ya tiene registro → sumamos 1 falta
        $conn->prepare("
            UPDATE cfaltas 
            SET Faltas = Faltas + 1,
                UltimaFecha = ? 
            WHERE Matricula = ?
        ")->execute([$hoy, $mat]);
    } else {
        // Alumno nuevo → insertamos por primera vez
        $conn->prepare("
            INSERT INTO cfaltas 
            (Matricula, Faltas, FechaUltimaFalta, UltimaFecha) 
            VALUES (?, 1, ?, ?)
        ")->execute([$mat, $hoy, $hoy]);
    }
}
