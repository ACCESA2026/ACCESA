<?php
header("Content-Type: application/json; charset=utf-8");
require_once "../Login/conexion.php"; // $conn (PDO)

$mat = trim($_GET["matricula"] ?? "");

if ($mat === "") {
    echo json_encode(["error" => "No se proporcionó matrícula"]);
    exit;
}

// Buscar alumno completo desde BD
$sql = $conn->prepare("
    SELECT 
        mc.Matricula,
        CONCAT(n.DsNombre,' ', ap1.DsApellido,' ', ap2.DsApellido) AS nombre,
        g.DsGrupo       AS grupo,
        s.DsSemestre    AS carrera
    FROM mcredencial mc
    LEFT JOIN cnombre   n   ON mc.CvNombre  = n.CvNombre
    LEFT JOIN capellido ap1 ON mc.CvApePat  = ap1.CvApellido
    LEFT JOIN capellido ap2 ON mc.CvApeMat  = ap2.CvApellido
    LEFT JOIN mgrupo    g   ON mc.CvGrupo   = g.CvGrupo
    LEFT JOIN msemestre s   ON mc.CvSemestre= s.CvSemestre
    WHERE mc.Matricula LIKE ?
    LIMIT 1
");
$sql->execute([$mat . '%']);
$alumno = $sql->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo json_encode(["error" => "No se encontró alumno en BD"]);
    exit;
}

/* ================== FOTO EXACTA ================== */
// Carpeta base: ACCESA/Foto_Estudiantes/
$carpetaFotos = realpath(__DIR__ . "/../Foto_Estudiantes");
$fotoFinal = null;

// Nombre exacto que debe tener la foto
// Ejemplo: 220700336-5 → 220700336-5.jpg
$nombreFoto = $alumno["Matricula"] . ".jpg";

if ($carpetaFotos && is_dir($carpetaFotos)) {
    $carpetas = scandir($carpetaFotos);

    foreach ($carpetas as $carpeta) {

        if ($carpeta === "." || $carpeta === "..") continue;

        $rutaCarpeta = $carpetaFotos . "/" . $carpeta;

        if (!is_dir($rutaCarpeta)) continue;

        // Ruta del archivo exacto
        $rutaFoto = $rutaCarpeta . "/" . $nombreFoto;

        if (file_exists($rutaFoto)) {
            // Ruta accesible desde navegador
            $fotoFinal = "../Foto_Estudiantes/$carpeta/$nombreFoto";
            break;
        }

    }
}

// Si no encontró foto, usar imagen default
if (!$fotoFinal) {
    $fotoFinal = "../Multimedia/sin_foto.png";
}

/* ============ RESPUESTA ============ */
echo json_encode([
    "matricula" => $alumno["Matricula"],
    "nombre"    => $alumno["nombre"],
    "grupo"     => $alumno["grupo"],
    "carrera"   => $alumno["carrera"],
    "hora"      => date("H:i:s"),
    "foto"      => $fotoFinal
]);
