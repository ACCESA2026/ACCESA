<?php
// ../Registro de alumnos/index.php
session_start();
if (empty($_SESSION['usuario'])) {
  header('Location: ../Login/login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registro de Alumnos · CONALEP</title>

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Look & feel global (fondo, tarjetas, grid) -->
  <link rel="stylesheet" href="../Menu/menu.css" />
  <!-- Encabezado con logo, usuario y fecha -->
  <link rel="stylesheet" href="../Centro_logo/header.css" />
  <!-- (Opcional) estilos propios de este módulo -->
  <link rel="stylesheet" href="registro.css" />
</head>
<body>

  <!-- Header unificado (muestra el nombre y la fecha tal como en index2.php) -->
  <?php include '../Centro_logo/header.php'; ?>

  <main class="container" role="main">
    <section class="grid" aria-label="Registro de estudiantes">

      <!-- Tile: Registrar 1 alumno -->
      <a class="tile" href="../Registro_alumnos/registro_unico.php" role="button" aria-label="Registrar 1 alumno">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V22h19.2v-2.8c0-3.2-6.4-4.8-9.6-4.8z"/>
          </svg>
        </span>
        <span class="label">Registrar 1 alumno</span>
      </a>

      <!-- Tile: Registrar varios -->
      <a class="tile" href="../Registro_alumnos/registro_varios.php" role="button" aria-label="Registrar varios alumnos">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.94 1.97 3.45V19h6v-2.5C23 14.57 19.33 13 16 13z"/>
          </svg>
        </span>
        <span class="label">Registrar varios</span>
      </a>

      <!-- Tile: Volver al menú -->
      <a class="tile" href="../Menu/menu.php" role="button" aria-label="Volver al menú">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
          </svg>
        </span>
        <span class="label">Volver al menú</span>
      </a>

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

  <!-- Si tu header usa JS para la fecha, inclúyelo aquí -->
  <script src="../Centro_logo/header.js"></script>
  <!-- Scripts propios del módulo (si los tienes) -->
  <script src="registro.js"></script>
</body>
</html>
