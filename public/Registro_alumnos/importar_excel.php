<?php
require_once '../Login/conexion.php'; // conexión PDO a MySQL

// ======================== VALIDACIÓN DE ARCHIVO ========================
$rutaArchivo = __DIR__ . '/Base de Datos Subidos/' . ($_POST['ruta_archivo'] ?? '');
if (!file_exists($rutaArchivo)) {
  die("<script>
    alert('❌ No se encontró el archivo Excel para importar.');
    window.location.href='registrar_varios.php';
  </script>");
}

// ======================== FUNCIONES AUXILIARES ========================
function getOrInsert($conn, $tabla, $columna, $valor, $clave = "id") {
  $valor = trim($valor);
  if ($valor === "") return null;

  $stmt = $conn->prepare("SELECT $clave FROM $tabla WHERE $columna = ?");
  $stmt->execute([$valor]);
  $id = $stmt->fetchColumn();

  if ($id) return $id;

  $stmt = $conn->prepare("INSERT INTO $tabla ($columna) VALUES (?)");
  $stmt->execute([$valor]);
  return $conn->lastInsertId();
}

// ======================== LEER EXCEL ========================
$zip = new ZipArchive;
if ($zip->open($rutaArchivo) !== TRUE) {
  die("<script>alert('No se pudo abrir el archivo XLSX.'); window.location.href='registrar_varios.php';</script>");
}

$sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
$sheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
$zip->close();

if (!$sharedStringsXML || !$sheetXML) {
  die("<script>alert('Archivo Excel no válido o dañado.'); window.location.href='registrar_varios.php';</script>");
}

$sharedStrings = [];
$sxmlShared = simplexml_load_string($sharedStringsXML);
foreach ($sxmlShared->si as $si) $sharedStrings[] = (string)$si->t;

$sxmlSheet = simplexml_load_string($sheetXML);
$rows = [];
foreach ($sxmlSheet->sheetData->row as $row) {
  $cells = [];
  foreach ($row->c as $c) {
    $valor = (string)$c->v;
    $tipo = (string)$c['t'];
    $cells[] = $tipo === 's' ? $sharedStrings[$valor] : $valor;
  }
  $rows[] = $cells;
}

// ======================== VALIDAR ENCABEZADOS ========================
$header = array_shift($rows);
$esperado = [
  "Matrícula","Primer apellido","Segundo apellido","Nombre","Grupo Referente",
  "Sexo","CURP","Fecha Nacimiento","Email Institucional","Plan Estudios",
  "Plan Estudios Cve","Tutor Nombre","Dom Colonia","Calle","Edad",
  "Médico Institución Médica","Dom CP","Dom Ciudad","Teléfono","Tel Celular",
  "E-mail","Municipio","Tutor celular"
];

if ($header !== $esperado) {
  die("<script>
    alert('❌ Los encabezados no coinciden. No se puede importar.');
    window.location.href='registrar_varios.php';
  </script>");
}

// ======================== PROCESAR FILAS ========================
$total = 0;
$insertados = 0;
$actualizados = 0;
$TIPO_PERSONA_ESTUDIANTE = 1;

foreach ($rows as $fila) {
  $total++;
  list($matricula, $apePat, $apeMat, $nombre, $grupoRef, $sexo, $curp, $fechaNac,
       $emailInst, $planEst, $planCve, $tutorNom, $colonia, $calle, $edad,
       $instMed, $cp, $ciudad, $tel, $cel, $emailPers, $municipio, $tutorCel) = array_pad($fila, 23, null);

  if (!$matricula) continue;

  try {
    // 1️⃣ Registrar matrícula en mmatricula
    getOrInsert($conn, "mmatricula", "DsMatricula", $matricula, "CvMatricula");

    // 2️⃣ Catálogos
    $cvNombre  = getOrInsert($conn, "cnombre", "DsNombre", $nombre, "CvNombre");
    $cvApePat  = getOrInsert($conn, "capellido", "DsApellido", $apePat, "CvApellido");
    $cvApeMat  = getOrInsert($conn, "capellido", "DsApellido", $apeMat, "CvApellido");
    $cvGenero  = getOrInsert($conn, "cgenero", "DsGenero", $sexo, "CvGenero");
    $cvColonia = getOrInsert($conn, "ccolonia", "DsColonia", $colonia, "CvColonia");
    $cvCalle   = getOrInsert($conn, "ccalle", "DsCalle", $calle, "CvCalle");
    $cvCiudad  = getOrInsert($conn, "cciudad", "DsCiudad", $ciudad, "CvCiudad");
    $cvMunicipio = getOrInsert($conn, "cmunicipio", "DsMunicipio", $municipio, "CvMunicipio");
    $cvInstMed = getOrInsert($conn, "cinstitucion_medica", "DsInstMedica", $instMed, "CvInstMedica");

    // 3️⃣ Dirección
    $stmt = $conn->prepare("
      INSERT INTO mdireccion (CvCalle, CvColonia, CvMunicipio, CvCiudad, CodPos)
      VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$cvCalle, $cvColonia, $cvMunicipio, $cvCiudad, $cp]);
    $cvDireccion = $conn->lastInsertId();

    // 4️⃣ Semestre y grupo
    $partes = explode('-', $grupoRef);
    $semCodigo = substr($partes[0], 0, 1);
    $cvSemestre = getOrInsert($conn, "msemestre", "DsSemestre", $semCodigo . "° Semestre", "CvSemestre");
    $cvGrupo = getOrInsert($conn, "mgrupo", "DsGrupo", $grupoRef, "CvGrupo");

    // 5️⃣ Plan de estudios
    $cvPlan = getOrInsert($conn, "mplanestudios", "DsPlan", $planEst, "CvPlan");

    // 6️⃣ Insertar o actualizar en mcredencial (usa Matricula texto)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM mcredencial WHERE Matricula = ?");
    $stmt->execute([$matricula]);
    $existe = $stmt->fetchColumn();

    if ($existe) {
      $sqlUpdate = "
        UPDATE mcredencial
        SET CvNombre=?, CvApePat=?, CvApeMat=?, CvGenero=?, CvSemestre=?, CvGrupo=?, CvPlan=?,
            CURP=?, FechaNacimiento=?, Edad=?, EmailInstitucional=?, EmailPersonal=?, Telefono=?, Celular=?,
            TutorNombreCompleto=?, CelularTutor=?, CvDireccion=?, CvInstitucionMedica=?, CvTipPersona=?
        WHERE Matricula=?
      ";
      $stmt = $conn->prepare($sqlUpdate);
      $stmt->execute([
        $cvNombre, $cvApePat, $cvApeMat, $cvGenero, $cvSemestre, $cvGrupo, $cvPlan,
        $curp, $fechaNac, $edad, $emailInst, $emailPers, $tel, $cel,
        $tutorNom, $tutorCel, $cvDireccion, $cvInstMed, $TIPO_PERSONA_ESTUDIANTE, $matricula
      ]);
      $actualizados++;
    } else {
      $sqlInsert = "
        INSERT INTO mcredencial
        (Matricula, CvNombre, CvApePat, CvApeMat, CvGenero, CvSemestre, CvGrupo, CvPlan,
         CURP, FechaNacimiento, Edad, EmailInstitucional, EmailPersonal, Telefono, Celular,
         TutorNombreCompleto, CelularTutor, CvDireccion, CvInstitucionMedica, CvTipPersona)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ";
      $stmt = $conn->prepare($sqlInsert);
      $stmt->execute([
        $matricula, $cvNombre, $cvApePat, $cvApeMat, $cvGenero, $cvSemestre, $cvGrupo, $cvPlan,
        $curp, $fechaNac, $edad, $emailInst, $emailPers, $tel, $cel,
        $tutorNom, $tutorCel, $cvDireccion, $cvInstMed, $TIPO_PERSONA_ESTUDIANTE
      ]);
      $insertados++;
    }

  } catch (Exception $e) {
    echo "<script>console.error('Error en fila $total: " . addslashes($e->getMessage()) . "');</script>";
  }
}

// ======================== RESULTADO FINAL ========================
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Importación completada</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
Swal.fire({
  icon: 'success',
  title: '✅ Importación completada',
  html: `
    <b>Total de filas procesadas:</b> <?= $total ?><br>
    <b>Insertados:</b> <?= $insertados ?><br>
    <b>Actualizados:</b> <?= $actualizados ?><br><br>
    <small>Archivo: <?= basename($rutaArchivo) ?></small>
  `,
  confirmButtonColor: '#0B4D3D'
}).then(() => {
  window.location.href = '../Registro%20de%20alumnos/registro_varios.php';
});
</script>
</body>
</html>