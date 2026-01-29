<?php
// /Mantenimiento_acceso/mantenimiento_acceso.php
$BASE = '/' . explode('/', trim($_SERVER['SCRIPT_NAME'] ?? '', '/'))[0]; // Ej: /Mantenimiento_acceso
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',         // 🔑 Esto permite que la cookie sea global
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['usuario'])) {
  header('Location: ../Login/login.php');
  exit;
}

$rol = $_SESSION['usuario']['rol'] ?? 'Invitado';
if ($rol !== 'Administrador') {
  header('Location: ../Menu/menu.php?error=permiso_denegado');
  exit;
}

$nombreCompleto = $_SESSION['usuario']['nombre_completo'] ?? ($_SESSION['usuario']['username'] ?? 'Invitado');
$nombreCompletoFmt = mb_convert_case($nombreCompleto, MB_CASE_TITLE, 'UTF-8');
$idUsuarioSesion = $_SESSION['usuario']['id'] ?? 0;

date_default_timezone_set('America/Mexico_City');
$fechaHoyPHP = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mantenimiento de acceso - Control de módulos</title>
  <link rel="stylesheet" href="mantenimiento_acceso.css" />
  <style>
    .brand-logo{height:50px;width:auto;display:block}
    .white-logo{filter:brightness(0) invert(1)}
    select {
      width: 100%;
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
      outline: none;
    }
    select:focus { border-color:#0B4D3D; box-shadow:0 0 3px #0B4D3D; }
    option[disabled] { color:#888; background:#f5f5f5; }
  </style>
    <style>
.toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background-color: #0B4D3D;
  color: white;
  padding: 12px 18px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  font-weight: bold;
  opacity: 0;
  z-index: 9999;
  transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
  transform: translateY(-20px);
}

.toast.show {
  opacity: 1;
  transform: translateY(0);
}
</style>

</head>
<body>

<header class="topbar">
  <div class="container topbar-row">
    <div class="brand">
      <img src="../Multimedia/Logo_CONALEP_horizontal.png" alt="CONALEP" class="brand-logo white-logo" />
    </div>
    <div class="top-actions">
      <div class="user-pill">
        <span class="user-dot"></span>
        Nombre completo: <strong><?php echo htmlspecialchars($nombreCompletoFmt); ?></strong>
      </div>
      <div class="user-pill">
        <span class="user-dot"></span>
        📅 <span id="today"><?php echo htmlspecialchars($fechaHoyPHP); ?></span>
      </div>
      <a href="../Menu/menu.php" class="btn btn-light">Atrás</a>
    </div>
  </div>
</header>

<main class="content">
  <div class="container">
    <h1 class="title-pill">Mantenimiento de acceso - Control de módulos</h1>

    <section class="card">
      <h2 class="card-title">Asignar Permisos de módulos a usuarios</h2>

      <!-- Selector de usuario -->
      <div class="row">
        <label for="usuario">Seleccionar usuario:</label>
        <select id="usuario">
          <option value="">Cargando usuarios...</option>
        </select>
      </div>

      <!-- Checkboxes -->
      <ul class="checks">
        <li><label><input type="checkbox" value="Registro de estudiantes"> Registro de estudiantes</label></li>
        <li><label><input type="checkbox" value="Historial de estudiantes"> Historial de estudiantes</label></li>
        <li><label><input type="checkbox" value="Control de accesos"> Control de accesos</label></li>
          <li><label><input type="checkbox" value="Reportes de alumnos"> Reportes de alumnos</label></li>
        <li><label><input type="checkbox" value="Configuración de sistema"> Configuración de sistema</label></li>
        <li><label><input type="checkbox" value="Gestión de usuarios"> Gestión de usuarios</label></li>
          <li><label><input type="checkbox" value="Datos personales"> Datos personales</label></li>
		<li><label><input type="checkbox" value="Mantenimiento de acceso"> Mantenimiento de acceso</label></li>
      </ul>

      <div class="actions">
        <button class="btn btn-success" type="button">Guardar Cambios</button>
        <button class="btn btn-danger" type="button">Cancelar</button>
      </div>
    </section>

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

<script>
const idUsuarioSesion = <?php echo json_encode($idUsuarioSesion); ?>;
const select = document.getElementById('usuario');
const checks = document.querySelectorAll('.checks input[type=checkbox]');

// --- Cargar usuarios disponibles ---
(async function cargarUsuarios(){
  try {
    const res = await fetch('get_usuarios.php');
    const data = await res.json();
    select.innerHTML = '<option value="">Seleccione un usuario...</option>';
    if(Array.isArray(data)){
      data.forEach(u=>{
        const opt=document.createElement('option');
        opt.value=u.CvUsuario;
        opt.textContent=`${u.NombreCompleto} (${u.rol ?? 'Sin rol'})`;
        if(+u.CvUsuario===+idUsuarioSesion){
          opt.disabled=true;
          opt.textContent+=' (Tu sesión actual)';
        }
        select.appendChild(opt);
      });
    }
  }catch{
    select.innerHTML='<option value="">Error al cargar usuarios</option>';
  }
})();

// --- Al seleccionar usuario, traer permisos actuales ---
select.addEventListener('change', async ()=>{
  const id=select.value;
  checks.forEach(c=>c.checked=false); // limpiar primero
  if(!id) return;

  try{
    const res=await fetch('get_permisos_usuario.php?id='+id);
    const data=await res.json();
    if(Array.isArray(data)){
      checks.forEach(c=>{
        if(data.includes(c.value)) c.checked=true;
      });
    }
  }catch(e){
    console.error(e);
    alert('Error al cargar permisos del usuario');
  }
});

function mostrarToast(mensaje, colorFondo = '#0B4D3D') {
  const toast = document.getElementById('toast');
  toast.textContent = mensaje;
  toast.style.backgroundColor = colorFondo;
  toast.classList.add('show');

  setTimeout(() => {
    toast.classList.remove('show');
  }, 3000); // Ocultar después de 3 segundos
}

// --- Guardar permisos con toast elegante ---
document.querySelector('.btn-success').addEventListener('click', async () => {
  const usuarioId = select.value;
  if (!usuarioId) {
    mostrarToast('⚠️ Seleccione un usuario primero', '#a94442');
    return;
  }

  const permisos = [];
  checks.forEach(chk => {
    if (chk.checked) permisos.push(chk.value);
  });

  try {
    const res = await fetch('guardarPermisosAjax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ usuarioId, permisos })
    });

    const data = await res.json();

    if (res.ok && data.success) {
      mostrarToast('✅ Permisos guardados correctamente');

      // 🔁 Limpiar selección y checks
      select.selectedIndex = 0;
      checks.forEach(chk => chk.checked = false);
    } else {
      mostrarToast('❌ Error al guardar: ' + (data.error ?? 'Error desconocido'), '#a94442');
    }

  } catch (err) {
    console.error(err);
    mostrarToast('❌ Error inesperado al guardar permisos', '#a94442');
  }
});
</script>
    <div id="toast" class="toast"></div>

</body>
</html>
