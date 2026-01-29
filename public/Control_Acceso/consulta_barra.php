<?php
header('Content-Type: application/json; charset=UTF-8');
require_once "../Login/conexion.php";

/* =====================================================
   1) Normalizar código recibido
   ===================================================== */
$raw = $_POST['codigo'] ?? $_POST['matricula'] ?? $_GET['codigo'] ?? $_GET['matricula'] ?? '';
if ($raw === '' || $raw === null) {
  $input = file_get_contents('php://input');
  parse_str($input, $parsed);
  $json = json_decode($input, true);
  if (is_array($json)) $parsed = $json;
  $raw = $parsed['codigo'] ?? $parsed['matricula'] ?? $parsed['text'] ?? $parsed['value'] ?? '';
}

$raw = trim((string)$raw);
$raw = preg_replace('/[^\x20-\x7E]/', '', $raw);
$matricula = preg_replace('/[^0-9A-Za-z\-]/', '', $raw);
$soloDigitos = preg_replace('/[^0-9]/', '', $raw);

if ($matricula === '' && $soloDigitos === '') {
  echo json_encode(["valido" => false, "msg" => "Código vacío"]);
  exit;
}

/* =====================================================
   2) Buscar alumno con joins correctos
   ===================================================== */
try {
  $sql = "
    SELECT 
      m.Matricula,
      CONCAT(n.DsNombre, ' ', ap.DsApellido, ' ', am.DsApellido) AS NombreCompleto,
      g.DsGrupo AS Grupo,
      m.CvGrupo,
      m.CvSemestre,
      m.CvPlan,
      m.CURP,
      m.Edad,
      m.EmailInstitucional
    FROM mcredencial m
      LEFT JOIN cnombre n ON m.CvNombre = n.CvNombre
      LEFT JOIN capellido ap ON m.CvApePat = ap.CvApellido
      LEFT JOIN capellido am ON m.CvApeMat = am.CvApellido
      LEFT JOIN mgrupo g ON m.CvGrupo = g.CvGrupo
    WHERE m.Matricula = ? 
    LIMIT 1
  ";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$matricula]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  // Intentar también sin guiones
  if (!$row && $soloDigitos !== '') {
    $sql = "
      SELECT 
        m.Matricula,
        CONCAT(n.DsNombre, ' ', ap.DsApellido, ' ', am.DsApellido) AS NombreCompleto,
        g.DsGrupo AS Grupo,
        m.CvGrupo,
        m.CvSemestre,
        m.CvPlan,
        m.CURP,
        m.Edad,
        m.EmailInstitucional
      FROM mcredencial m
        LEFT JOIN cnombre n ON m.CvNombre = n.CvNombre
        LEFT JOIN capellido ap ON m.CvApePat = ap.CvApellido
        LEFT JOIN capellido am ON m.CvApeMat = am.CvApellido
        LEFT JOIN mgrupo g ON m.CvGrupo = g.CvGrupo
      WHERE REPLACE(REPLACE(TRIM(m.Matricula),'-',''),' ','') = ?
      LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$soloDigitos]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /* =====================================================
     3) Buscar foto real
     ===================================================== */
  if ($row) {
    $fotoNombre = $row['Matricula'] . '.jpg';
    $carpetas = [
      '../Foto_Estudiantes/Primer_Semestre/',
      '../Foto_Estudiantes/Segundo_Semestre/',
      '../Foto_Estudiantes/Tercer_Semestre/',
      '../Foto_Estudiantes/Cuarto_Semestre/',
      '../Foto_Estudiantes/Quinto_Semestre/',
      '../Foto_Estudiantes/Sexto_Semestre/'
    ];

    $fotoPath = '';
    foreach ($carpetas as $ruta) {
      if (file_exists($ruta . $fotoNombre)) {
        $fotoPath = $ruta . $fotoNombre;
        break;
      }
    }

    if ($fotoPath !== '') {
      $fotoURL = str_replace('..', 'https://accesa.infinityfree.me', $fotoPath);
    } else {
      $fotoURL = 'https://accesa.infinityfree.me/Foto_Estudiantes/default.png';
    }

    $row['Foto'] = $fotoURL;

    echo json_encode([
      "valido" => true,
      "data" => $row
    ], JSON_UNESCAPED_UNICODE);
  } else {
    echo json_encode([
      "valido" => false,
      "msg" => "Credencial no encontrada en BD",
      "recibido" => $raw,
      "normalizado" => $matricula
    ], JSON_UNESCAPED_UNICODE);
  }

} catch (Throwable $e) {
  echo json_encode([
    "valido" => false,
    "msg" => "Error del servidor",
    "error" => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
exit;
?>
