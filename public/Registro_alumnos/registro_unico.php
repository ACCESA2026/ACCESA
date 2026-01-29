<?php
session_start();
if (empty($_SESSION['usuario'])) {
  header('Location: ../Login/login.php');
  exit;
}

require_once "../Login/conexion.php";

date_default_timezone_set('America/Mexico_City');
$nombreCompleto = $_SESSION['usuario']['nombre_completo'] ?? ($_SESSION['usuario']['username'] ?? 'Admin');
$nombreCompletoFmt = mb_convert_case($nombreCompleto, MB_CASE_TITLE, 'UTF-8');
$fechaHoyPHP = date("d/m/Y");
$fotoPerfil = $_SESSION['usuario']['foto'] ?? 'default.png'; // Foto por defecto
/* FUNCIÓN PARA CARGAR CATÁLOGOS */
function cargarCatalogo($conn, $tabla, $campoID, $campoDS) {
    $sql = "SELECT $campoID, $campoDS FROM $tabla ORDER BY $campoDS ASC";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll();
}

/* CARGAR CATÁLOGOS */
$catGenero   = cargarCatalogo($conn, "cgenero", "CvGenero", "DsGenero");
$catSemestre = cargarCatalogo($conn, "msemestre", "CvSemestre", "DsSemestre");
$catGrupo    = cargarCatalogo($conn, "mgrupo", "CvGrupo", "DsGrupo");
$catPlan     = cargarCatalogo($conn, "mplanestudios", "CvPlan", "DsPlan");
$catInstMed  = cargarCatalogo($conn, "cinstitucion_medica", "CvInstMedica", "DsInstMedica");

/* GUARDAR */
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $matricula = trim($_POST["matricula"]);
    $curp = trim($_POST["curp"]);
    $edad = (int)$_POST["edad"];
    $nombre = trim($_POST["nombre"]);
    $apep = trim($_POST["apep"]);
    $apem = trim($_POST["apem"]);
    $genero = (int)$_POST["genero"];
    $nac = $_POST["nac"];
    $correo_inst = trim($_POST["correo_institucional"]);
    $correo_per = trim($_POST["correo_personal"]);
    $sem = (int)$_POST["sem"];
    $grupo = (int)$_POST["grupo"];
    $plan = (int)$_POST["plan"];
    $telefono = trim($_POST["telefono"]);
    $celular = trim($_POST["celular"]);
    $tutor = trim($_POST["tutor"]);
    $tutor_tel = trim($_POST["tutor_tel"]);
    $direccionTexto = trim($_POST["direccion"]);
    $instMed = (int)$_POST["inst_medica"];

    /* Verificar si la matrícula ya existe */
    $stmt = $conn->prepare("SELECT COUNT(*) FROM mcredencial WHERE Matricula = ?");
    $stmt->execute([$matricula]);
    $matriculaExistente = $stmt->fetchColumn();

    if ($matriculaExistente > 0) {
        $mensaje = "❌ Error: La matrícula $matricula ya está registrada.";
    } else {
        /* NOMBRE */
        $stmt = $conn->prepare("INSERT INTO cnombre (DsNombre) VALUES (?)");
        $stmt->execute([$nombre]);
        $CvNombre = $conn->lastInsertId();

        /* APELLIDOS */
        $stmt = $conn->prepare("INSERT INTO capellido (DsApellido) VALUES (?)");
        $stmt->execute([$apep]);
        $CvApePat = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO capellido (DsApellido) VALUES (?)");
        $stmt->execute([$apem]);
        $CvApeMat = $conn->lastInsertId();

        /* DIRECCIÓN (B1) */
        $stmt = $conn->prepare("INSERT INTO ccalle (DsCalle) VALUES (?)");
        $stmt->execute([$direccionTexto]);
        $CvCalle = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO mdireccion (CvCalle) VALUES (?)");
        $stmt->execute([$CvCalle]);
        $CvDireccion = $conn->lastInsertId();

        /* INSERCIÓN FINAL */
        $sql = "INSERT INTO mcredencial (
                  Matricula, CvNombre, CvApePat, CvApeMat,
                  CvSemestre, CvGrupo, CvPlan, CURP,
                  CvGenero, FechaNacimiento, Edad,
                  EmailInstitucional, EmailPersonal,
                  Telefono, Celular, TutorNombreCompleto, CelularTutor,
                  CvTipPersona, CvDireccion, CvInstitucionMedica
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $matricula, $CvNombre, $CvApePat, $CvApeMat,
            $sem, $grupo, $plan, $curp,
            $genero, $nac, $edad,
            $correo_inst, $correo_per,
            $telefono, $celular, $tutor, $tutor_tel,
            1, // Alumno
            $CvDireccion,
            $instMed
        ]);

        $mensaje = "✔ Estudiante registrado correctamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Registro de estudiante · CONALEP</title>
    <link rel="stylesheet" href="../estilos/responsive.css">
    <link rel="stylesheet" href="registro_unico.css" />
</head>

<body>

<header class="topbar">
  <div class="container topbar-row">
    <div class="brand">
      <img src="../Multimedia/Logo_CONALEP_horizontal.png" alt="CONALEP" class="brand-logo white-logo" />
    </div>

    <!-- Mostramos la foto de perfil y nombre completo desde header.php -->
    <div class="user-pill">
      <img src="../Menu/fotos_perfil/<?php echo htmlspecialchars($fotoPerfil); ?>" class="foto-perfil" alt="Foto de perfil">
      <span>Nombre completo: <strong><?php echo htmlspecialchars($nombreCompletoFmt); ?></strong></span>
    </div>

    <div class="user-pill">
      <span class="user-dot"></span>
      <span>📅 <span id="today"><?php echo htmlspecialchars($fechaHoyPHP); ?></span></span>
    </div>
    <a href="../Menu/menu.php" class="btn btn-light">Atrás</a>
  </div>
</header>

<main class="content">

    <div class="container">

        <h1 class="title-pill">Registro de estudiante</h1>

        <?php if ($mensaje): ?>
            <div style="background:#d4edda;padding:10px;border-radius:8px;color:#155724;margin-bottom:15px;">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <form class="form-grid" method="POST">

                <!-- CAMPOS (LOS MANTENGO TAL CUAL LOS DEJAMOS) -->

                <div class="field">
                    <label>Matrícula</label>
                    <input type="text" name="matricula" required>
                </div>

                <div class="field">
                    <label>CURP</label>
                    <input type="text" name="curp" required>
                </div>

                <div class="field">
                    <label>Edad</label>
                    <input type="number" name="edad" min="0" required>
                </div>

                <div class="field">
                    <label>Nombre(s)</label>
                    <input type="text" name="nombre" required>
                </div>

                <div class="field">
                    <label>Apellido paterno</label>
                    <input type="text" name="apep" required>
                </div>

                <div class="field">
                    <label>Apellido materno</label>
                    <input type="text" name="apem" required>
                </div>

                <div class="field">
                    <label>Género</label>
                    <select name="genero" required>
                        <option value="">Seleccionar…</option>
                        <?php foreach ($catGenero as $g): ?>
                            <option value="<?= $g['CvGenero'] ?>"><?= $g['DsGenero'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="nac" required>
                </div>

                <div class="field">
                    <label>Correo institucional</label>
                    <input type="email" name="correo_institucional" required>
                </div>

                <div class="field">
                    <label>Correo personal</label>
                    <input type="email" name="correo_personal">
                </div>

                <div class="field">
                    <label>Semestre</label>
                    <select name="sem" required>
                        <?php foreach ($catSemestre as $s): ?>
                            <option value="<?= $s['CvSemestre'] ?>"><?= $s['DsSemestre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Grupo</label>
                    <select name="grupo" required>
                        <?php foreach ($catGrupo as $g): ?>
                            <option value="<?= $g['CvGrupo'] ?>"><?= $g['DsGrupo'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Plan de estudio</label>
                    <select name="plan" required>
                        <?php foreach ($catPlan as $p): ?>
                            <option value="<?= $p['CvPlan'] ?>"><?= $p['DsPlan'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Teléfono</label>
                    <input type="text" name="telefono">
                </div>

                <div class="field">
                    <label>Celular</label>
                    <input type="text" name="celular">
                </div>

                <div class="field">
                    <label>Nombre completo del tutor</label>
                    <input type="text" name="tutor">
                </div>

                <div class="field">
                    <label>Celular del tutor</label>
                    <input type="text" name="tutor_tel">
                </div>

                <div class="field">
                    <label>Tipo de persona</label>
                    <input type="text" value="Alumno" disabled>
                </div>

                <div class="field field-wide">
                    <label>Dirección completa</label>
                    <input type="text" name="direccion" required>
                </div>

                <div class="field">
                    <label>Institución médica</label>
                    <select name="inst_medica" required>
                        <option value="">Seleccionar…</option>
                        <?php foreach ($catInstMed as $im): ?>
                            <option value="<?= $im['CvInstMedica'] ?>"><?= $im['DsInstMedica'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="actions">
                    <button class="btn btn-success" type="submit">Guardar</button>
                    <button class="btn btn-secondary" type="reset">Limpiar</button>
                    <a class="btn btn-danger" href="../Menu/menu.php">Cancelar</a>
                </div>

            </form>
        </section>

    </div>
</main>

</body>
</html>
