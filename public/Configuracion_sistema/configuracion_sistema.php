<?php
session_start();
if (empty($_SESSION['usuario'])) {
  header('Location: ../Login/login.php');
  exit;
}

require_once __DIR__ . '/../Login/conexion.php';

date_default_timezone_set('America/Mexico_City');
$fechaHoyPHP = date('d/m/Y');
$fotoPerfil = $_SESSION['usuario']['foto'] ?? 'default.png';
$rutaFoto = "../Menu/fotos_perfil/" . $fotoPerfil;
$nombreCompleto = $_SESSION['usuario']['nombre_completo'] ?? ($_SESSION['usuario']['username'] ?? 'Admin');

// ===============================
// LEER ÚLTIMO RESPALDO (ARCHIVO)
// ===============================
$archivoEstado = __DIR__ . '/ultimo_respaldo.json';
$ultimoRespaldo = 'No disponible';
$estadoRespaldo = 'OK';

if (file_exists($archivoEstado)) {
  $contenido = json_decode(file_get_contents($archivoEstado), true);

  if (!empty($contenido['ultimo_respaldo'])) {
    $ultimoRespaldo = date('d/m/Y · h:i a', strtotime($contenido['ultimo_respaldo']));
  }

  $estadoRespaldo = $contenido['estado'] ?? 'OK';
}

// ===============================
// ✅ REPORTES: Total + Último reporte (desde BD)
// ===============================
$totalReportes = 0;
$ultimoReporteTxt = 'No disponible';

try {
  // Total de reportes
  $totalReportes = (int)$conn->query("SELECT COUNT(*) FROM rReportesAlumnos")->fetchColumn();

  // Detectar PK automáticamente (para ordenar bien)
  $pkCol = null;
  $stmtPk = $conn->prepare("
    SELECT k.COLUMN_NAME
    FROM information_schema.TABLE_CONSTRAINTS t
    JOIN information_schema.KEY_COLUMN_USAGE k
      ON t.CONSTRAINT_NAME = k.CONSTRAINT_NAME
     AND t.TABLE_SCHEMA = k.TABLE_SCHEMA
     AND t.TABLE_NAME = k.TABLE_NAME
    WHERE t.CONSTRAINT_TYPE = 'PRIMARY KEY'
      AND t.TABLE_SCHEMA = DATABASE()
      AND t.TABLE_NAME = 'rReportesAlumnos'
    LIMIT 1
  ");
  $stmtPk->execute();
  $pkCol = $stmtPk->fetchColumn();

  // Armar ORDER BY seguro
  // 1) si hay PK: ORDER BY PK DESC
  // 2) si no hay PK: ORDER BY FechaIncidente DESC (si existe)
  $orderBy = '';
  if (!empty($pkCol)) {
    $orderBy = "ORDER BY `$pkCol` DESC";
  } else {
    $orderBy = "ORDER BY FechaIncidente DESC";
  }

  // Último reporte
  $sqlUlt = "
    SELECT Matricula, NombreAlumno, TipoReporte, FechaIncidente
    FROM rReportesAlumnos
    $orderBy
    LIMIT 1
  ";
  $ult = $conn->query($sqlUlt)->fetch(PDO::FETCH_ASSOC);

  if ($ult) {
    $fechaFmt = !empty($ult['FechaIncidente']) ? date('d/m/Y', strtotime($ult['FechaIncidente'])) : '';
    $mat = $ult['Matricula'] ?? '';
    $nom = $ult['NombreAlumno'] ?? '';
    $tipo = $ult['TipoReporte'] ?? '';

    // Texto bonito (como pediste)
    $ultimoReporteTxt = trim($mat . " · " . $nom . " · " . $tipo . ($fechaFmt ? " · " . $fechaFmt : ""));
    if ($ultimoReporteTxt === '' || $ultimoReporteTxt === '···') $ultimoReporteTxt = 'No disponible';
  }
} catch (Throwable $e) {
  // Si algo falla, no tronamos la vista
  $totalReportes = 0;
  $ultimoReporteTxt = 'No disponible';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Configuración de sistema · CONALEP</title>

  <!-- Tipografía -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Estilos -->
  <link rel="stylesheet" href="../Centro_logo/header.css" />
  <link rel="stylesheet" href="configuracion_sistema.css" />

  <style>
    /* ======== Encabezado centrado estilo uniforme ======== */
    .topbar {
      background-color: #0b4d3d;
      color: #fff;
      padding: 0.8rem 0;
      font-family: 'Inter', sans-serif;
    }

    .topbar-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
    }

    .topbar-logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .topbar-logo img {
      height: 50px;
      width: auto;
      filter: brightness(0) invert(1);
    }

    .topbar-info {
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    /* Fondo blanco para nombre y fecha */
    .pill {
      background: #fff;
      border-radius: 999px;
      padding: 0.3rem 0.8rem;
      display: flex;
      align-items: center;
      font-size: 0.85rem;
      color: #0b4d3d;
      font-weight: 600;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .user-icon {
      margin-right: 0.3rem;
      color: #0b4d3d;
      opacity: 0.9;
    }

    .btn-back {
      background: #fff;
      color: #0b4d3d;
      font-weight: 600;
      padding: 0.3rem 0.9rem;
      border-radius: 999px;
      text-decoration: none;
      border: 2px solid transparent;
      transition: 0.2s;
      box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }

    .btn-back:hover {
      background: transparent;
      color: #fff;
      border: 2px solid #fff;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .topbar-container {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }
      .topbar-info {
        flex-direction: column;
        gap: 0.4rem;
        margin-top: 0.5rem;
      }
    }

    /* =========================
       MODAL REPORTES (OVERLAY)
    ========================= */
    .modal-overlay{
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.55);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 18px;
    }

    .modal-overlay.is-open{
      display: flex;
    }

    .modal-card{
      width: min(720px, 96vw);
      background: #fff;
      border-radius: 22px;
      padding: 22px 22px 18px;
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      max-height: 86vh;
      overflow: auto;
      font-family: 'Inter', sans-serif;
    }

    .modal-title{
      margin: 0 0 14px;
      font-size: 1.35rem;
      font-weight: 800;
      color: #0b4d3d;
    }

    .modal-grid{
      display: grid;
      gap: 12px;
    }

    .modal-grid .field label{
      display:block;
      font-weight: 700;
      color:#0b4d3d;
      margin-bottom: 6px;
    }

    .modal-grid .field input,
    .modal-grid .field select,
    .modal-grid .field textarea{
      width: 100%;
      border: 1px solid #cfe1de;
      background: #eef5f4;
      border-radius: 10px;
      padding: 10px 12px;
      outline: none;
    }

    .modal-grid .field textarea{
      resize: vertical;
      min-height: 90px;
    }

    .modal-actions{
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 16px;
    }

    body.modal-open{
      overflow: hidden;
    }

    /* Alerta de éxito */
    .alert.success{
      background: #e8fff3;
      border: 1px solid #b5f0cf;
      color: #0b4d3d;
      padding: 12px 14px;
      border-radius: 12px;
      margin: 10px auto 14px;
      max-width: 1200px;
      font-weight: 700;
    }
  </style>
</head>

<body>

<!-- Encabezado -->
<header class="topbar">
  <div class="topbar-container">

    <div class="topbar-logo">
      <img src="../Multimedia/Logo_CONALEP_horizontal.png" alt="CONALEP Chiapas" />
    </div>

    <div class="topbar-info">

      <!-- FOTO DE PERFIL -->
      <div class="pill profile-pill">
        <img src="<?= $rutaFoto ?>" class="profile-img" alt="Foto de perfil">
        <div>
          <span>Nombre completo:</span>
          <strong><?= htmlspecialchars($nombreCompleto) ?></strong>
        </div>
      </div>

      <!-- FECHA -->
      <div class="pill">
        <span class="user-icon">📅</span>
        <?= $fechaHoyPHP ?>
      </div>

      <!-- BOTÓN ATRÁS -->
      <a href="../Menu/menu.php" class="btn-back">Atrás</a>
    </div>

  </div>
</header>

<!-- Contenido principal -->
<main class="content">

<?php if (isset($_GET['reporte']) && $_GET['reporte'] === 'ok'): ?>
  <div class="alert success" id="alertReporteOk">✔ Reporte registrado correctamente</div>
<?php endif; ?>


  <div class="container">
    <h1 class="title-pill">Configuración de sistema</h1>

    <!-- ============================
           SECCIÓN: RESPALDO
    ============================== -->
    <section class="card">
      <h2 class="card-title">Respaldo de datos</h2>
      <p class="card-subtitle">Gestión y programación de copias de seguridad del sistema</p>

      <!-- Estado -->
      <div class="subcard">
        <h3>Estado del respaldo</h3>

        <div class="status-grid">
          <div class="status-item ok">
            <span class="icon">✔</span>
            <div>
              <strong>Respaldos automáticos</strong>
              <small>Actualmente activados</small>
            </div>
          </div>

          <div class="status-item">
            <span class="icon">📅</span>
            <div>
              <strong>Último respaldo</strong>
              <small><?= htmlspecialchars($ultimoRespaldo) ?></small>
            </div>
          </div>

          <div class="status-item">
            <span class="icon">💽</span>
            <div>
              <strong>Estado</strong>
              <small>Correcto</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Programación -->
      <div class="subcard">
        <h3>Programación</h3>

        <label class="switch-row switch-enhanced">
          <input type="checkbox" checked>
          <span class="slider"></span>
          <span>
            Respaldos automáticos
            <small>El sistema generará copias de seguridad según la programación</small>
          </span>
        </label>

        <div class="grid g-3 mt-12">
          <div class="field">
            <label for="frecuencia">Frecuencia</label>
            <select id="frecuencia">
              <option>Diario</option>
              <option>Semanal</option>
              <option>Mensual</option>
            </select>
          </div>

          <div class="field">
            <label for="horaBack">Hora de ejecución</label>
            <input id="horaBack" type="time" value="02:00" />
          </div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="subcard actions-card">
        <h3>Acciones manuales</h3>

        <button class="btn btn-success btn-large" type="button"
                onclick="window.location.href='generar_respaldo_excel.php'">
          💾 Ejecutar respaldo ahora
        </button>

        <small class="hint">Genera una copia inmediata de la base de datos del sistema.</small>
      </div>
    </section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const alertOk = document.getElementById('alertReporteOk');
  if (!alertOk) return;

  // Se oculta a los 6 segundos
  setTimeout(() => {
    alertOk.style.transition = 'opacity .35s ease, transform .35s ease';
    alertOk.style.opacity = '0';
    alertOk.style.transform = 'translateY(-6px)';

    // lo quitamos del DOM después del fade
    setTimeout(() => {
      alertOk.remove();
    }, 400);
  }, 6000);
});
</script>

</body>
</html>
