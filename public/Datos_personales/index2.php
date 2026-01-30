<?php
// Datos_personales/datos_person_estudiante.php
session_start();
if (empty($_SESSION['usuario'])) {
  header('Location: ../Login/login.php');
  exit;
}

require_once "../Login/conexion.php";
date_default_timezone_set('America/Mexico_City');
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// ======================================================
// GUARDAR / ACTUALIZAR ESTUDIANTE
// ======================================================
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='save') {
  $matricula = trim($_POST['matricula'] ?? '');
  $nombre = trim($_POST['nombre'] ?? '');
  $apep = trim($_POST['apep'] ?? '');
  $apem = trim($_POST['apem'] ?? '');
  $curp = trim($_POST['curp'] ?? '');
  $genero = trim($_POST['genero'] ?? '');
  $nacimiento = trim($_POST['nacimiento'] ?? '');
  $edad = trim($_POST['edad'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $correo = trim($_POST['correo'] ?? '');
  $carrera = trim($_POST['carrera'] ?? '');
  $semestre = trim($_POST['semestre'] ?? '');
  $grupo = trim($_POST['grupo'] ?? '');
  $estatus = trim($_POST['estatus'] ?? '');

  // Calcular edad si no está
  if ($nacimiento && !$edad) {
    try {
      $dob = new DateTime($nacimiento);
      $edad = (new DateTime('today'))->diff($dob)->y;
    } catch(Exception $e) { $edad=''; }
  }

  // Mapeo o inserción automática en catálogos
  function getOrCreate($conn, $tabla, $campo, $valor, $pk) {
    if (!$valor) return null;
    $stmt = $conn->prepare("SELECT $pk FROM $tabla WHERE $campo=? LIMIT 1");
    $stmt->execute([$valor]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) return $row[$pk];
    $ins = $conn->prepare("INSERT INTO $tabla ($campo) VALUES (?)");
    $ins->execute([$valor]);
    return $conn->lastInsertId();
  }

  $cvNom = getOrCreate($conn, "cnombre", "DsNombre", $nombre, "CvNombre");
  $cvApePat = getOrCreate($conn, "capellido", "DsApellido", $apep, "CvApellido");
  $cvApeMat = getOrCreate($conn, "capellido", "DsApellido", $apem, "CvApellido");
  $cvGen = getOrCreate($conn, "cgenero", "DsGenero", $genero, "CvGenero");
  $cvSem = getOrCreate($conn, "msemestre", "DsSemestre", $semestre, "CvSemestre");
  $cvTurn = getOrCreate($conn, "mturno", "DsTurno", $turno, "CvTurno");

  // Verificar si ya existe la matrícula
  $check = $conn->prepare("SELECT Matricula FROM mcredencial WHERE Matricula = ?");
  $check->execute([$matricula]);

  if ($check->fetch()) {
    // ---- UPDATE ----
    $upd = $conn->prepare("
      UPDATE mcredencial SET
        CvNombre=?, CvApePat=?, CvApeMat=?, FechaNacimiento=?, Edad=?, CURP=?, 
        CvGenero=?, Telefono=?, EmailInstitucional=?, 
        CvSemestre=?, CvTurno=?, CvGrupo=?, Estatus='Activo'
      WHERE Matricula=?
    ");
    $upd->execute([
      $cvNom, $cvApePat, $cvApeMat, $nacimiento, $edad, $curp,
      $cvGen, $telefono, $correo,
      $cvSem, $cvTurn, $grupo, $matricula
    ]);
  } else {
    // ---- INSERT ----
    $ins = $conn->prepare("
      INSERT INTO mcredencial (
        Matricula, CvNombre, CvApePat, CvApeMat, CvTurno, CvSemestre, CvGrupo,
        CURP, CvGenero, FechaNacimiento, Edad, EmailInstitucional, Telefono, CvTipPersona
      ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)
    ");
    $ins->execute([
      $matricula, $cvNom, $cvApePat, $cvApeMat, $cvTurn, $cvSem, $grupo,
      $curp, $cvGen, $nacimiento, $edad, $correo, $telefono
    ]);
  }

  header("Location: datos_person_estudiante.php?saved=1");
  exit;
}

// ======================================================
// CARGAR TODOS LOS REGISTROS PARA LA TABLA
// ======================================================
$stmt = $conn->query("
  SELECT 
    c.Matricula,
    CONCAT(n.DsNombre, ' ', ap.DsApellido, ' ', am.DsApellido) AS NombreCompleto,
    s.DsSemestre AS Semestre,
    gpo.DsGrupo AS Grupo,
    t.DsTurno AS Turno,
    c.CURP,
    c.Edad,
    c.FechaNacimiento,
    cg.DsGenero AS Genero,
    c.EmailInstitucional AS Correo,
    c.Telefono
  FROM mcredencial c
  LEFT JOIN cnombre n ON c.CvNombre = n.CvNombre
  LEFT JOIN capellido ap ON c.CvApePat = ap.CvApellido
  LEFT JOIN capellido am ON c.CvApeMat = am.CvApellido
  LEFT JOIN msemestre s ON c.CvSemestre = s.CvSemestre
  LEFT JOIN mgrupo gpo ON c.CvGrupo = gpo.CvGrupo
  LEFT JOIN mturno t ON c.CvTurno = t.CvTurno
  LEFT JOIN cgenero cg ON c.CvGenero = cg.CvGenero
  ORDER BY c.Matricula ASC
");

$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Datos personales de estudiantes · CONALEP</title>

  <link rel="stylesheet" href="../Menu/menu.css" />
  <link rel="stylesheet" href="../Centro_logo/header.css" />
  <link rel="stylesheet" href="datos_person_estudiante.css" />
</head>
<body>
  <?php include '../Centro_logo/header.php'; ?>

  <main class="dp-container">
    <div class="page-bar">
      <h1 class="title">Datos Personales De Estudiantes</h1>
      <a class="btn ghost" href="../Menu/menu.php">← Volver al menú</a>
    </div>

    <section class="panel">
      <h2 class="panel-title">Ficha del estudiante</h2>
      <?php if (isset($_GET['saved'])): ?><div class="alert success">Registro guardado correctamente.</div><?php endif; ?>

      <form class="dp-form" method="post" action="datos_person_estudiante.php">
        <input type="hidden" name="action" value="save">

        <div class="grid g3 row">
          <div class="field"><label>Matrícula</label><input id="matricula" name="matricula" type="text" required></div>
          <div class="field"><label>Nombre(s)</label><input id="nombre" name="nombre" type="text" required></div>
          <div class="field"><label>CURP</label><input id="curp" name="curp" type="text" maxlength="18"></div>
        </div>

        <div class="grid g3 row">
          <div class="field"><label>Apellido paterno</label><input id="apep" name="apep" type="text"></div>
          <div class="field"><label>Apellido materno</label><input id="apem" name="apem" type="text"></div>
          <div class="field">
            <label>Género</label>
		<select id="genero" name="genero">
  	<option value="">Seleccionar…</option>
  <?php
  $generos = $conn->query("SELECT DsGenero FROM cgenero ORDER BY DsGenero")->fetchAll(PDO::FETCH_COLUMN);
  foreach ($generos as $g) {
      echo "<option value='$g'>$g</option>";
  }
  ?>
		</select>

          </div>
        </div>

        <div class="grid g3 row">
          <div class="field"><label>Fecha de nacimiento</label><input id="nacimiento" name="nacimiento" type="date"></div>
          <div class="field xs"><label>Edad</label><input id="edad" name="edad" type="number" min="0" readonly></div>
          <div class="field"><label>Teléfono</label>
              <input id="telefono" name="telefono" type="tel" 
       pattern="[0-9]{10}" maxlength="10" minlength="10" 
       title="Debe contener exactamente 10 dígitos numéricos" 
       oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10)" required></div>
        </div>

        <div class="grid g3 row">
          <div class="field"><label>Correo institucional</label><input id="correo" name="correo" type="email"></div>
          <div class="field xs"><label>Semestre</label>
            <select id="semestre" name="semestre"><?php for($i=1;$i<=6;$i++) echo "<option>$i</option>"; ?></select>
          </div>
          <div class="field xs"><label>Grupo</label>
            <select id="grupo" name="grupo">
    <option value="">Seleccionar…</option>
    <?php
    $grupos = $conn->query("SELECT DsGrupo FROM mgrupo ORDER BY DsGrupo ASC")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($grupos as $g) {
        echo "<option value='$g'>$g</option>";
    }
    ?>
</select>

          </div>
        </div>

        <div class="grid g2 row">
        </div>

        <div class="row actions">
          <button class="btn primary" type="submit">Guardar / Actualizar</button>
        </div>
      </form>
    </section>

    <section class="panel table-panel">
      <div class="table-head">
        <h2 class="table-title">Estudiantes registrados</h2>
        <input id="q" class="filter" type="search" placeholder="Buscar...">
      </div>

      <div class="table-wrap">
        <table class="table">
          <thead><tr>
            <th>#</th><th>Matrícula</th><th>Nombre completo</th><th>Semestre</th>
            <th>Grupo</th><th>Turno</th><th>CURP</th><th>Correo</th><th>Teléfono</th>
          </tr></thead>
<tbody id="tb">
<?php if($alumnos): foreach($alumnos as $i=>$a): ?>
  <tr class="rowAlumno"
      data-matricula="<?= h($a['Matricula']) ?>"
      data-nombre="<?= h($a['NombreCompleto']) ?>"
      data-curp="<?= h($a['CURP']) ?>"
      data-semestre="<?= h($a['Semestre']) ?>"
      data-grupo="<?= h($a['Grupo']) ?>"
      data-correo="<?= h($a['Correo']) ?>"
      data-telefono="<?= h($a['Telefono']) ?>"
      data-edad="<?= h($a['Edad']) ?>"
	data-genero="<?= h($a['Genero']) ?>"
  	data-nacimiento="<?= h($a['FechaNacimiento']) ?>"

  >
    <td><?= $i+1 ?></td>
    <td><?= h($a['Matricula']) ?></td>
    <td><?= h($a['NombreCompleto']) ?></td>
    <td><?= h($a['Semestre']) ?></td>
    <td><?= h($a['Grupo']) ?></td>
    <td><?= h($a['Turno']) ?></td>
    <td><?= h($a['CURP']) ?></td>
    <td><?= h($a['Correo']) ?></td>
    <td><?= h($a['Telefono']) ?></td>
  </tr>
<?php endforeach; else: ?>
  <tr><td colspan="9" class="empty">Aún no hay registros.</td></tr>
<?php endif; ?>
</tbody>


        </table>
      </div>
    </section>
  </main>
  <script>
document.querySelectorAll(".rowAlumno").forEach(row => {
  row.addEventListener("click", () => {

    // --- Separar nombre completo ---
    let partes = row.dataset.nombre.split(" ");
    let nombre = partes.shift();
    let apep = partes.shift();
    let apem = partes.join(" ");

    // --- Llenar formulario ---
    document.getElementById("matricula").value = row.dataset.matricula;
    document.getElementById("nombre").value = nombre ?? "";
    document.getElementById("apep").value = apep ?? "";
    document.getElementById("apem").value = apem ?? "";
    document.getElementById("curp").value = row.dataset.curp ?? "";
    document.getElementById("telefono").value = row.dataset.telefono ?? "";
    document.getElementById("correo").value = row.dataset.correo ?? "";
    document.getElementById("edad").value = row.dataset.edad ?? "";
    // --- Llenar Fecha de nacimiento y Género ---
document.getElementById("nacimiento").value = row.dataset.nacimiento ?? "";

// Establecer género si coincide con alguna opción (convirtiendo ambos a minúsculas)
const genero = (row.dataset.genero ?? "").toLowerCase();
const selectGenero = document.getElementById("genero");
[...selectGenero.options].forEach(opt => {
  if (opt.value.toLowerCase() === genero) selectGenero.value = opt.value;
});


// --- Llenar Semestre ---
let sem = row.dataset.semestre.trim().replace(/[^0-9]/g, "");
document.getElementById("semestre").value = sem;

// --- Llenar Grupo ---
let grp = row.dataset.grupo.trim();
document.getElementById("grupo").value = grp;

  });
});
</script>
<script>
const inputBuscador = document.getElementById("q");
const filas = document.querySelectorAll("#tb tr");

inputBuscador.addEventListener("input", () => {
    let txt = inputBuscador.value.trim().toLowerCase();

    // Si hay menos de 3 caracteres → mostrar todo
    if (txt.length < 3) {
        filas.forEach(f => f.style.display = "");
        return;
    }

    filas.forEach(fila => {
        let contenido = fila.innerText.toLowerCase();
        if (contenido.includes(txt)) {
            fila.style.display = "";
        } else {
            fila.style.display = "none";
        }
    });
});
</script>


</body>
</html>
 