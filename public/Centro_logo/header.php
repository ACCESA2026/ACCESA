<?php
// ../Centro_logo/header.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$nombreCompleto = $_SESSION['usuario']['nombre_completo']
    ?? ($_SESSION['usuario']['username'] ?? 'Invitado');
$nombreCompletoFmt = mb_convert_case($nombreCompleto, MB_CASE_TITLE, 'UTF-8');

$fotoPerfil = $_SESSION['usuario']['foto'] ?? 'default.png';

date_default_timezone_set('America/Mexico_City');
$fechaPHP = date('d/m/Y');
?>

<link rel="stylesheet" href="../Centro_logo/header_dropdown.css">

<div class="topbar" role="banner">

  <div class="brand">
    <img src="../Multimedia/Logo_CONALEP_horizontal.png"
         alt="CONALEP" class="brand-logo white-logo" />
  </div>

  <div class="meta">

    <!-- CONTENEDOR AISLADO DEL DROPDOWN -->
    <div class="dropdown-wrapper">

      <div class="dropdown">
        <button class="chip dropdown-btn" id="userDropdownBtn">
          <!-- Aquí se agrega la imagen de perfil redondeada -->
          <img src="../Menu/fotos_perfil/<?php echo htmlspecialchars($fotoPerfil); ?>" 
     class="mini-foto" alt="Foto de perfil">
          <span><strong>Nombre completo:</strong> <?php echo htmlspecialchars($nombreCompletoFmt); ?></span>

          <svg class="arrow" width="14" height="14" viewBox="0 0 24 24">
            <path d="M7 10l5 5 5-5z" fill="#0b3d32"/>
          </svg>
        </button>

        <!-- MENU -->
        <div class="dropdown-menu" id="userDropdownMenu">
          <a href="../Menu/info_usuario.php" class="dropdown-item">Información del usuario</a>
          <a href="../Login/logout.php" class="dropdown-item logout">Cerrar sesión</a>
        </div>
      </div>

    </div>

    <!-- ===== FECHA ===== -->
    <div class="chip">
      <span class="chip-icon">📅</span>
      <time id="today"><?php echo htmlspecialchars($fechaPHP); ?></time>
    </div>
  </div>
      
</div>
<style>
<style>
.dropdown-btn {
    overflow: visible !important;
    display: flex;
    align-items: center;
}

.mini-foto {
    width: 45px !important;
    height: 45px !important;
    border-radius: 50% !important;
    object-fit: cover !important;
    flex-shrink: 0 !important;
}
</style>

</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("userDropdownBtn");
  const menu = document.getElementById("userDropdownMenu");

  btn.addEventListener("click", () => {
    menu.classList.toggle("open");
  });

  // cerrar si clic afuera
  document.addEventListener("click", (e) => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove("open");
    }
  });
});
</script>

