<?php
// /Menu/menu.php
    $BASE = '/' . explode('/', trim($_SERVER['SCRIPT_NAME'] ?? '', '/'))[0]; // Ej: /Mantenimiento_acceso
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',         // 🔑 Esto permite que la cookie sea global
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/../Login/conexion.php';

if (empty($_SESSION['usuario'])) {
  header('Location: ../Login/login.php');
  exit;
}

$rol = $_SESSION['usuario']['rol'] ?? 'Invitado';
$idUsuario = $_SESSION['usuario']['id'] ?? 0;

// OJO: corrige el nombre real del rol (Desarrollador vs Desarrolador)
$esAdmin = ($rol === 'Administrador' || $rol === 'Desarrollador');

if ($esAdmin) {
  $permisos = [
    'Registro de estudiantes',
    'Historial de estudiantes',
    'Control de accesos',
    'Reportes de alumnos',  
    'Datos_personales',
    'Configuración de sistema',
    'Gestión de usuarios',
    'Mantenimiento de acceso'
  ];
} else {
  $permisos = [];
  try {
    $stmt = $conn->prepare("
      SELECT modulo
      FROM maccesos
      WHERE CvUsuario = ? AND tiene_acceso = 1
    ");
    $stmt->execute([$idUsuario]);
    $permisos = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
  } catch (Throwable $e) {
    $permisos = [];
  }
}

function tienePermiso($permisos, $modulo) {
  return in_array($modulo, $permisos, true); // true = comparación estricta
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CONALEP · Panel principal</title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="menu.css" />
  <style>
    .tile.disabled {
      pointer-events: none;
      background-color: #d9d9d9;
      color: #777;
      box-shadow: none;
      opacity: 0.6;
      cursor: not-allowed;
      position: relative;
    }
    .tile.disabled span.icon svg {
      fill: #777;
    }
  </style>
</head>
<body>
  <?php include '../Centro_logo/header.php'; ?>

  <main class="container" role="main">
    <section class="grid" aria-label="Menú principal">

      <!-- Registro de estudiantes -->
      <?php if (tienePermiso($permisos, 'Registro de estudiantes')): ?>
        <a class="tile" href="../Registro_alumnos/index.php" role="button">
          <span class="icon">
            <svg viewBox="0 0 24 24">
              <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.94 1.97 3.45V19h6v-2.5C23 14.57 19.33 13 16 13z"/>
            </svg>
          </span>
          <span class="label">Registro de<br/>estudiantes</span>
        </a>
      <?php else: ?>
        <div class="tile disabled"><span class="label">Registro de<br/>estudiantes</span></div>
      <?php endif; ?>

      <!-- Historial de estudiantes -->
      <?php if (tienePermiso($permisos, 'Historial de estudiantes')): ?>
        <a class="tile" href="../Historial/historial.php" role="button">
          <span class="icon">
            <svg viewBox="0 0 24 24">
              <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V22h19.2v-2.8c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
          </span>
          <span class="label">Historial de<br/>estudiantes</span>
        </a>
      <?php else: ?>
        <div class="tile disabled"><span class="label">Historial de<br/>estudiantes</span></div>
      <?php endif; ?>

      <!-- Control de accesos -->
      <?php if (tienePermiso($permisos, 'Control de accesos')): ?>
        <a class="tile" href="../Control_Acceso/control_acceso.php" role="button">
          <span class="icon">
            <svg viewBox="0 0 24 24">
              <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm10 0h2v2h-2v-2zm0 4h2v2h-2v-2zm4-4h2v6h-2v-6zm-4 0h2v2h-2v-2z"/>
            </svg>
          </span>
          <span class="label">Control de<br/>accesos</span>
        </a>
      <?php else: ?>
        <div class="tile disabled"><span class="label">Control de<br/>accesos</span></div>
      <?php endif; ?>
        <!-- Reportes de alumnos -->
<?php if (tienePermiso($permisos, 'Reportes de alumnos')): ?>
  <a class="tile" href="../Reportes_alumnos/reportes_alumnos.php" role="button">
    <span class="icon">
      <svg viewBox="0 0 24 24">
        <path d="M4 19h16v2H4v-2zm1-3h3V8H5v8zm5 0h3V4h-3v12zm5 0h3v-6h-3v6z"/>
      </svg>
    </span>
    <span class="label">Reportes de<br/>alumnos</span>
  </a>
<?php else: ?>
  <div class="tile disabled">
    <span class="label">Reportes de<br/>alumnos</span>
  </div>
<?php endif; ?>

      <!-- Datos personales -->
<?php if (tienePermiso($permisos, 'Datos_personales')): ?>
<a class="tile" href="../public/Datos_personales/index2.php" role="button">
        <span class="icon">
            <svg viewBox="0 0 24 24">
                <path d="M3 5h18a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm2 3v8h14V8H5zm3 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zm4 1h5v2h-5v-2zm0 3h4v2h-4v-2z"/>
            </svg>
        </span>

        <span class="label">
            Datos personales<br/>de estudiantes
        </span>
    </a>
<?php else: ?>
    <div class="tile disabled">
        <span class="label">
            Datos personales<br/>de estudiantes
        </span>
    </div>
<?php endif; ?>


      <!-- Configuración del sistema -->
      <?php if (tienePermiso($permisos, 'Configuración de sistema')): ?>
        <a class="tile" href="../Configuracion_sistema/configuracion_sistema.php" role="button">
          <span class="icon">
            <svg viewBox="0 0 24 24">
              <path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.03 7.03 0 0 0-1.63-.94l-.36-2.54A.5.5 0 0 0 13.9 1h-3.8a.5.5 0 0 0-.5.42l-.36 2.54c-.58.22-1.12.53-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.7 7.02a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94L2.82 12.7a.5.5 0 0 0-.12.64l1.92 3.32c.13.22.39.31.6.22l2.39-.96c.5.41 1.05.72 1.63.94l.36 2.54c.04.23.26.4.5.4h3.8c.24 0 .46-.17.5-.4l.36-2.54c.58-.22 1.12-.53 1.63-.94l2.39.96c.22.09.47 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58z"/>
            </svg>
          </span>
          <span class="label">Configuración del<br/>sistema</span>
        </a>
      <?php else: ?>
        <div class="tile disabled"><span class="label">Configuración del<br/>sistema</span></div>
      <?php endif; ?>

      <!-- Gestión de usuarios -->
      <?php if (tienePermiso($permisos, 'Gestión de usuarios')): ?>
        <a class="tile" href="../Gestion_usuario/gestion_usuario.php" role="button">
          <span class="icon">
            <svg viewBox="0 0 24 24">
              <path d="M10 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm-3 5c-2.67 0-8 1.34-8 4v2h6.09A6.97 6.97 0 0 1 5 17c0-.68.09-1.33.26-1.95C6.5 14.41 8.2 13.99 10 13.99zm8-5a3 3 0 1 1-6 .01A3 3 0 0 1 15 8zm5 9a3 3 0 1 0-6 0 3 3 0 0 0 6 0zm-8 4h-2a7 7 0 0 1 12-4v2a9 9 0 0 0-10 2z"/>
            </svg>
          </span>
          <span class="label">Gestión de<br/>usuarios</span>
        </a>
      <?php else: ?>
        <div class="tile disabled"><span class="label">Gestión de<br/>usuarios</span></div>
      <?php endif; ?>

      <!-- Mantenimiento de acceso -->
      <?php if (tienePermiso($permisos, 'Mantenimiento de acceso')): ?>
        <a class="tile" href="../Mantenimiento_acceso/mantenimiento_acceso.php" role="button">
          <span class="icon">
            <svg viewBox="0 0 24 24">
              <path d="M4 5h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm0 2v9h16V7H4zm5 13h6v2H9v-2z"/>
            </svg>
          </span>
          <span class="label">Mantenimiento de<br/>acceso</span>
        </a>
      <?php else: ?>
        <div class="tile disabled"><span class="label">Mantenimiento de<br/>acceso</span></div>
      <?php endif; ?>


    </section>
  </main>

<footer class="footer">
  <small>
    © All Right Reserved. Designed by
    <a href="https://conalepcomitan070.edu.mx/" target="_blank" rel="noopener noreferrer">
      CONALEP Comitán Clave 070
    </a>
  </small>
</footer>

  </footer>
</body>
</html>
