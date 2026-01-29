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
$nombreCompleto = $_SESSION['usuario']['nombre_completo']
  ?? ($_SESSION['usuario']['username'] ?? 'Admin');

/* ================= MENSAJE DE ÉXITO ================= */
$reporteGuardado = (isset($_GET['reporte']) && $_GET['reporte'] === 'ok');

/* ================= REPORTES ================= */
$totalReportes = 0;
$ultimoReporteTxt = 'No disponible';

try {
  $totalReportes = (int)$conn
    ->query("SELECT COUNT(*) FROM rreportesalumnos")
    ->fetchColumn();

  $ult = $conn->query("
    SELECT Matricula, NombreAlumno, TipoReporte, FechaIncidente
    FROM rreportesalumnos
    ORDER BY FechaIncidente DESC
    LIMIT 1
  ")->fetch(PDO::FETCH_ASSOC);

  if ($ult) {
    $fecha = $ult['FechaIncidente']
      ? date('d/m/Y', strtotime($ult['FechaIncidente']))
      : '';
    $ultimoReporteTxt = trim(
      ($ult['Matricula'] ?? '') . ' · ' .
      ($ult['NombreAlumno'] ?? '') . ' · ' .
      ($ult['TipoReporte'] ?? '') .
      ($fecha ? ' · '.$fecha : '')
    );
  }
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reportes de alumnos · CONALEP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Tipografía -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="reportes_alumnos.css">
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
          .alert.success {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #e8fff3;
  border: 1px solid #b5f0cf;
  color: #0b4d3d;
  padding: 14px 18px;
  border-radius: 14px;
  margin: 14px auto 18px;
  max-width: 1200px;
  font-weight: 600;
  box-shadow: 0 8px 25px rgba(0,0,0,.08);
}

.alert-icon {
  font-size: 1.4rem;
}

.fade-in {
  animation: fadeInSlide .4s ease;
}

@keyframes fadeInSlide {
  from {
    opacity: 0;
    transform: translateY(-6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

  </style>
</head>

<body>

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
      <a href="../Menu/menu.php" class="btn-back">Atrás</a>
    </div>


  </div>
</header>
<?php if ($reporteGuardado): ?>
  <div id="alertReporteOk" class="alert success">
    ✅ El reporte del alumno fue guardado correctamente.
  </div>
<?php endif; ?>
<!-- ===== CONTENIDO ===== -->
<main class="content">
  <div class="container">

    <h1 class="title-pill">Reportes de alumnos</h1>
    <p class="card-subtitle">
      Registro y seguimiento de incidencias disciplinarias dentro del plantel
    </p>

     <section class="card mt-20">
      <h2 class="card-title">Reportes de alumnos</h2>
      <p class="card-subtitle">Registro y seguimiento de incidencias disciplinarias dentro del plantel</p>

      <!-- Estado -->
      <div class="subcard">
        <h3>Estado de los reportes</h3>

        <div class="status-grid">
          <div class="status-item ok">
            <span class="icon">✔</span>
            <div>
              <strong>Reportes habilitados</strong>
              <small>El sistema permite registrar incidencias</small>
            </div>
          </div>

          <div class="status-item">
            <span class="icon">📊</span>
            <div>
              <strong>Total de reportes</strong>
              <small><?= (int)$totalReportes ?> reportes registrados</small>
            </div>
          </div>

          <div class="status-item">
            <span class="icon">🕒</span>
            <div>
              <strong>Último reporte</strong>
              <small><?= htmlspecialchars($ultimoReporteTxt) ?></small>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="subcard actions-card">
        <h3>Acciones</h3>

        <button class="btn btn-warning btn-large" type="button" id="btnAbrirReporte">
          🚨 Registrar nuevo reporte
        </button>

        <small class="hint">Registra un incidente relacionado con la conducta de un alumno.</small>
      </div>
    </section>

    <!-- Pie -->
    <footer class="footer">
<footer class="footer">
  <small>
    © All Right Reserved. Designed by
    <a href="https://conalepcomitan070.edu.mx/" target="_blank" rel="noopener noreferrer">
      CONALEP Comitán Clave 070
    </a>
  </small>
</footer>

  </div>
    
</main>


<!-- ============================
     MODAL: REPORTE DE ALUMNO
============================== -->
<div id="modalReporte" class="modal-overlay" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="tituloModalReporte">
    <h2 class="modal-title" id="tituloModalReporte">Registrar reporte de alumno</h2>

    <form id="formReporteAlumno"
          action="guardar_reporte_alumno.php"
          method="POST"
          enctype="multipart/form-data"
          class="modal-grid">

      <div class="field">
        <label for="matricula">Matrícula del alumno</label>
        <input type="text" id="matricula" name="matricula" required placeholder="Ej. 240700005-4">
      </div>

      <div class="field">
        <label for="nombreAlumno">Nombre del alumno</label>
        <input type="text" id="nombreAlumno" name="nombreAlumno" readonly placeholder="Se llenará automáticamente">
      </div>

      <div class="field">
        <label for="tipoReporte">Tipo de reporte</label>
        <select id="tipoReporte" name="tipoReporte" required>
          <option value="">Seleccione una opción</option>
          <option>Fumar dentro del plantel</option>
          <option>Conducta inapropiada</option>
          <option>Daño a instalaciones</option>
          <option>Falta de respeto</option>
          <option>Incumplimiento de reglamento</option>
          <option>Otro</option>
        </select>
      </div>

      <div class="field">
        <label for="descripcion">Descripción del incidente</label>
        <textarea id="descripcion" name="descripcion" rows="4" required></textarea>
      </div>

      <div class="field">
        <label for="fechaIncidente">Fecha del incidente</label>
        <input type="date" id="fechaIncidente" name="fechaIncidente" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="field">
        <label for="evidencia">Evidencia (opcional)</label>
        <input type="file" id="evidencia" name="evidencia" accept=".jpg,.jpeg,.png,.pdf">
        <small>Imagen o PDF</small>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="btnCerrarReporte">Cancelar</button>
        <button type="submit" class="btn btn-success">Guardar reporte</button>
      </div>

    </form>
  </div>
</div>

<!-- ============================
     JS: MODAL + AUTOLLENADO
============================== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btnAbrir = document.getElementById('btnAbrirReporte');
  const btnCerrar = document.getElementById('btnCerrarReporte');
  const modal = document.getElementById('modalReporte');

  const inputMatricula = document.getElementById('matricula');
  const inputNombre = document.getElementById('nombreAlumno');

  if (!btnAbrir || !modal) return;

  function openModal(){
    modal.classList.add('is-open');
    document.body.classList.add('modal-open');
    setTimeout(() => inputMatricula?.focus(), 50);
  }

  function closeModal(){
    modal.classList.remove('is-open');
    document.body.classList.remove('modal-open');
  }

  btnAbrir.addEventListener('click', openModal);
  btnCerrar?.addEventListener('click', closeModal);

  // Cerrar al dar clic fuera
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  // Cerrar con ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  // =============================
  // Autollenado por matrícula
  // =============================
  let timer = null;

  inputMatricula?.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => buscarAlumno(), 400);
  });

  function buscarAlumno() {
    const matricula = (inputMatricula?.value || '').trim();

    if (!matricula) {
      inputNombre.value = '';
      return;
    }

    fetch(`buscar_alumno.php?matricula=${encodeURIComponent(matricula)}`)
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          inputNombre.value = data.nombre;
        } else {
          inputNombre.value = '';
        }
      })
      .catch(() => {
        inputNombre.value = '';
        alert('Error al consultar alumno');
      });
  }
});
</script>
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

<script>
if (window.location.search.includes('reporte=ok')) {
  const url = new URL(window.location);
  url.searchParams.delete('reporte');
  window.history.replaceState({}, document.title, url.pathname);
}
</script>

</body>
</html>
