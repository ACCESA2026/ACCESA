<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../Login/conexion.php';

/* ========= fuerza el nombre completo y foto desde BD si hay username ========= */
if (!empty($_SESSION['usuario']['username'])) {
  try {
    $user = $_SESSION['usuario']['username'];

    $st = $conn->prepare("
      SELECT 
        IFNULL(NULLIF(NombreCompleto,''), Nombre) AS NombreCompleto,
        foto
      FROM musuarios
      WHERE Nombre = ? OR Usuario = ?
      LIMIT 1
    ");
    $st->execute([$user, $user]);
    if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      if (!empty($row['NombreCompleto'])) {
        $_SESSION['usuario']['nombre_completo'] = $row['NombreCompleto'];
      }
      if (!empty($row['foto'])) {
        $_SESSION['usuario']['foto'] = $row['foto'];
      }
    }
  } catch (Throwable $e) {
    // no rompemos la UI si falla
  }
}

/* ========= variables para header y sesión ========= */
$SELF_ID       = (int)($_SESSION['usuario']['id'] ?? 0);
$SELF_USERNAME = $_SESSION['usuario']['username'] ?? '';

$nombreCompleto    = $_SESSION['usuario']['nombre_completo'] ?? ($_SESSION['usuario']['username'] ?? 'Invitado');
$nombreCompletoFmt = mb_convert_case($nombreCompleto, MB_CASE_TITLE, 'UTF-8');

date_default_timezone_set('America/Mexico_City');
$fechaHoyPHP = date('d/m/Y');

$fotoPerfil = $_SESSION['usuario']['foto'] ?? 'default.png'; // Foto por defecto

/* ========= CSRF ========= */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf'];

/* ========= helpers ========= */
function json_exit($code, $payload){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

/* ========= roles ========= */
function rolesOptions(PDO $conn): array {
  $allowed = ['Alumno','Director','Vigilante','Administrador','Desarrollador','Docente'];
  $normalize = [
    'Alumno'=>'Alumno','Usuario'=>'Alumno',
    'Director'=>'Director','Gefe'=>'Director','Jefe'=>'Director',
    'Vigilante'=>'Vigilante','Vigilancia'=>'Vigilante','Guardia'=>'Vigilante',
    'Administrador'=>'Administrador','Admin'=>'Administrador',
    'Desarrollador'=>'Desarrollador','Dev'=>'Desarrollador',
    'Docente'=>'Docente','Maestro'=>'Docente','Profesor'=>'Docente',
  ];

  // en tu BD la tabla es: ctippersona
  $q = $conn->query("SELECT CvTipPersona AS id, TRIM(DsTipPersona) AS nombre FROM ctippersona");
  $rows = $q->fetchAll(PDO::FETCH_ASSOC);
  $byName = [];

  foreach ($rows as $r) {
    $orig = $r['nombre'];
    $norm = $normalize[$orig] ?? $orig;
    if (in_array($norm, $allowed, true) && !isset($byName[$norm])) {
      $byName[$norm] = (int)$r['id'];
    }
  }

  // crea roles que falten
  foreach ($allowed as $name) {
    if (!isset($byName[$name])) {
      $st = $conn->prepare("INSERT INTO ctippersona (DsTipPersona) VALUES (:n)");
      $st->execute([':n'=>$name]);
      $byName[$name] = (int)$conn->lastInsertId();
    }
  }

  $out = [];
  foreach ($allowed as $name) {
    $out[] = ['id'=>$byName[$name],'nombre'=>$name];
  }
  return $out;
}

function validarFechaRango(?string $ini, ?string $fin): bool {
  if (!$ini || !$fin) return true;
  return strtotime($ini) <= strtotime($fin);
}

/* ============== API AJAX ============== */
if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    json_exit(403, ['ok'=>false,'msg'=>'CSRF inválido. Recarga la página.']);
  }

  $action = $_POST['action'] ?? '';

  try {
    /* CREATE */
    if ($action === 'create') {
      $usuario  = trim($_POST['usuario'] ?? '');
      $nomComp  = trim($_POST['nombre'] ?? '');
      $estado   = ($_POST['estado'] ?? '') === 'Inactivo' ? 'Inactivo' : 'Activo';
      $rolId    = (int)($_POST['rol_id'] ?? 0);
      $pass1    = $_POST['password'] ?? '';
      $pass2    = $_POST['password2'] ?? '';
      $fini     = $_POST['fecha_ini'] ?: null;
      $ffin     = $_POST['fecha_fin'] ?: null;

      if ($usuario === '') json_exit(422, ['ok'=>false,'msg'=>'El usuario es obligatorio.']);
      if ($nomComp === '') json_exit(422, ['ok'=>false,'msg'=>'El nombre completo es obligatorio.']);
      if ($rolId <= 0) json_exit(422, ['ok'=>false,'msg'=>'Selecciona un rol.']);
      if ($pass1 === '' || $pass2 === '') json_exit(422, ['ok'=>false,'msg'=>'Ingresa y confirma la contraseña.']);
      if ($pass1 !== $pass2) json_exit(422, ['ok'=>false,'msg'=>'Las contraseñas no coinciden.']);
      if (strlen($pass1) < 8) json_exit(422, ['ok'=>false,'msg'=>'La contraseña debe tener al menos 8 caracteres.']);
      if (!validarFechaRango($fini, $ffin)) json_exit(422, ['ok'=>false,'msg'=>'La fecha final no puede ser menor que la de inicio.']);

      // usuario único en Nombre o Usuario
      $st = $conn->prepare("SELECT 1 FROM musuarios WHERE Nombre = ? OR Usuario = ?");
      $st->execute([$usuario, $usuario]);
      if ($st->fetch()) {
        json_exit(409, ['ok'=>false,'msg'=>'El usuario ya existe.']);
      }

      $hash = password_hash($pass1, PASSWORD_BCRYPT);

      // adaptado a tu estructura de BD
      $sql = "INSERT INTO musuarios
              (Nombre, Usuario, NombreCompleto, Password, FechaRegistro, CvTipPersona, Estado, FechaInicio, FechaFin)
              VALUES (:u, :u, :nc, :p, CURDATE(), :rol, :est, :fi, :ff)";
      $st = $conn->prepare($sql);
      $st->execute([
        ':u'   => $usuario,
        ':nc'  => $nomComp,
        ':p'   => $hash,
        ':rol' => $rolId,
        ':est' => $estado,
        ':fi'  => $fini,
        ':ff'  => $ffin
      ]);

      json_exit(200, ['ok'=>true,'msg'=>'Usuario creado']);
    }

    /* LIST */
    if ($action === 'list') {
      $buscar = trim($_POST['buscar'] ?? '');
      $rolId  = (int)($_POST['rol_id'] ?? 0);
      $estado = $_POST['estado'] ?? '';

      $where = [];
      $args  = [];

      if ($buscar !== '') {
        $where[] = "(u.Usuario LIKE :b OR u.Nombre LIKE :b OR IFNULL(u.NombreCompleto,'') LIKE :b)";
        $args[':b'] = "%{$buscar}%";
      }
      if ($rolId > 0) {
        $where[] = "u.CvTipPersona = :r";
        $args[':r'] = $rolId;
      }
      if ($estado === 'Activo' || $estado === 'Inactivo') {
        $where[] = "u.Estado = :e";
        $args[':e'] = $estado;
      }

      $sql = "SELECT 
                u.CvUsuario,
                u.Nombre,
                u.Usuario,
                IFNULL(u.NombreCompleto,'') AS NombreCompleto,
                u.Estado,
                u.FechaRegistro,
                u.FechaInicio,
                u.FechaFin,
                IFNULL(t.DsTipPersona,'') AS Rol
              FROM musuarios u
              LEFT JOIN ctippersona t ON t.CvTipPersona = u.CvTipPersona";
      if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
      }
      $sql .= " ORDER BY u.CvUsuario DESC LIMIT 200";

      $st = $conn->prepare($sql);
      $st->execute($args);

      json_exit(200, ['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /* GET */
    if ($action === 'get') {
      $id = (int)($_POST['id'] ?? 0);
      $st = $conn->prepare("SELECT * FROM musuarios WHERE CvUsuario = ?");
      $st->execute([$id]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      if (!$r) {
        json_exit(404, ['ok'=>false,'msg'=>'No encontrado']);
      }
      json_exit(200, ['ok'=>true,'data'=>$r]);
    }

    /* UPDATE */
    if ($action === 'update') {
      $id      = (int)($_POST['id'] ?? 0);
      $usuario = trim($_POST['usuario'] ?? '');
      $nomComp = trim($_POST['nombre'] ?? '');
      $estado  = ($_POST['estado'] ?? '') === 'Inactivo' ? 'Inactivo' : 'Activo';
      $rolId   = (int)($_POST['rol_id'] ?? 0);
      $pass1   = $_POST['password'] ?? '';
      $fini    = $_POST['fecha_ini'] ?: null;
      $ffin    = $_POST['fecha_fin'] ?: null;

      if ($usuario === '' || $nomComp === '') {
        json_exit(422, ['ok'=>false,'msg'=>'Completa los campos obligatorios.']);
      }
      if ($rolId <= 0) {
        json_exit(422, ['ok'=>false,'msg'=>'Selecciona un rol.']);
      }
      if ($pass1 === '') {
        json_exit(422, ['ok'=>false,'msg'=>'Escribe la contraseña actual para confirmar.']);
      }
      if (!validarFechaRango($fini, $ffin)) {
        json_exit(422, ['ok'=>false,'msg'=>'La fecha final no puede ser menor que la de inicio.']);
      }

      $st = $conn->prepare("SELECT Password FROM musuarios WHERE CvUsuario = ?");
      $st->execute([$id]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      if (!$r) {
        json_exit(404, ['ok'=>false,'msg'=>'Usuario no encontrado.']);
      }
      if (!password_verify($pass1, $r['Password'])) {
        json_exit(401, ['ok'=>false,'msg'=>'Contraseña incorrecta.']);
      }

      $sql = "UPDATE musuarios
              SET Nombre        = :u,
                  Usuario       = :u,
                  NombreCompleto= :nc,
                  Estado        = :est,
                  CvTipPersona  = :rol,
                  FechaInicio   = :fi,
                  FechaFin      = :ff
              WHERE CvUsuario   = :id";

      $st = $conn->prepare($sql);
      $st->execute([
        ':u'   => $usuario,
        ':nc'  => $nomComp,
        ':est' => $estado,
        ':rol' => $rolId,
        ':fi'  => $fini,
        ':ff'  => $ffin,
        ':id'  => $id
      ]);

      json_exit(200, ['ok'=>true,'msg'=>'Usuario actualizado']);
    }

    /* DELETE */
    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $st = $conn->prepare("DELETE FROM musuarios WHERE CvUsuario = ?");
      $st->execute([$id]);
      json_exit(200, ['ok'=>true,'msg'=>'Eliminado']);
    }

    if ($action === 'roles') {
      json_exit(200, ['ok'=>true,'data'=>rolesOptions($conn)]);
    }

    json_exit(400, ['ok'=>false,'msg'=>'Acción no soportada']);

  } catch (Throwable $e) {
    // siempre devolvemos JSON para que el front no truene
    json_exit(500, [
      'ok'     => false,
      'msg'    => 'Error en el servidor: ' . $e->getMessage(),
      'linea'  => $e->getLine(),
      'archivo'=> $e->getFile()
    ]);
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gestión de usuario - ACCESA</title>
  <link rel="stylesheet" href="gestion_usuario.css" />
  <style>
    .brand-logo{height:50px;width:auto;display:block;}
    .white-logo{filter:brightness(0) invert(1);}
    .sep{opacity:.5;margin:0 .25rem}
    .msg{font-size:.9rem;margin:.5rem 0}
    .msg.ok{color:#0a7a3d}
    .msg.err{color:#b3261e}
    .btn[disabled]{opacity:.6;cursor:not-allowed}
  </style>
</head>
<body>
<header class="topbar">
  <div class="container topbar-row">
    <div class="brand">
      <img src="../Multimedia/Logo_CONALEP_horizontal.png" alt="CONALEP" class="brand-logo white-logo" />
    </div>

    <!-- Foto de perfil y nombre completo -->
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
    <h1 class="page-title">Gestión de usuario - ACCESA</h1>

    <section class="card">
      <div class="card-header">
        <a href="#" class="card-link">Detalle / Crear Usuario</a>
        <button id="btn-nuevo" class="btn btn-mini" type="button">+ Nuevo usuario</button>
      </div>

      <form id="frm" class="form-grid" autocomplete="off">
        <input type="hidden" id="id">
        <input type="hidden" id="csrf" value="<?php echo htmlspecialchars($CSRF, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="field">
          <label for="usuario">Usuario:</label>
          <input id="usuario" name="usuario" type="text" required>
        </div>

        <div class="field">
          <label for="nombre">Nombre Completo</label>
          <input id="nombre" name="nombre" type="text" placeholder="Nombre(s) y apellidos" required>
        </div>

        <div class="field">
          <label for="estado">Estado:</label>
          <select id="estado" name="estado">
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
          </select>
        </div>

        <div class="field">
          <label for="rol">Rol principal:</label>
          <select id="rol" name="rol" required></select>
        </div>

        <div class="field">
          <label for="password">Contraseña <small>(para confirmar cambios)</small>:</label>
          <input id="password" name="password" type="password" placeholder="••••••••">
        </div>

        <div class="field">
          <label for="password2">Confirmar Contraseña <small>(solo en alta)</small>:</label>
          <input id="password2" name="password2" type="password" placeholder="••••••••">
        </div>

        <div class="field">
          <label for="fecha_ini">Fecha de inicio</label>
          <input id="fecha_ini" name="fecha_ini" type="date">
        </div>

        <div class="field">
          <label for="fecha_fin">Fecha de final</label>
          <input id="fecha_fin" name="fecha_fin" type="date">
        </div>
      </form>

      <div class="actions">
        <button id="btn-guardar" class="btn btn-success" type="button">Guardar</button>
        <button id="btn-editar" class="btn btn-primary" type="button" disabled>Confirmar</button>
        <button id="btn-cancelar" class="btn btn-danger" type="button">Cancelar</button>
      </div>
      <div id="msg" class="msg"></div>
    </section>

    <section class="card">
      <h2 class="card-title">Buscar Usuario</h2>
      <div class="filters">
        <input id="f-buscar" class="input" type="text" placeholder="Usuario o nombre...">
        <div class="filter-pair">
          <label>Rol:</label>
          <select id="f-rol">
            <option value="0">Seleccionar…</option>
          </select>
        </div>
        <div class="filter-pair">
          <label>Estado:</label>
          <select id="f-estado">
            <option value="">Seleccionar…</option>
            <option>Activo</option>
            <option>Inactivo</option>
          </select>
        </div>
        <button id="btn-filtrar" class="btn btn-mini" type="button">Filtrar</button>
      </div>

      <div class="table-wrap">
        <table class="table" id="tabla">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Nombre completo</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbody"></tbody>
        </table>
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
const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));

const setMsg = (t, ok = true) => {
  const el = $('#msg');
  el.className = 'msg ' + (ok ? 'ok' : 'err');
  el.textContent = t;
  if (t) setTimeout(() => { el.textContent=''; el.className='msg'; }, 3500);
};

const busy = v => {
  $('#btn-guardar').disabled = v;
  $('#btn-editar').disabled  = v || !$('#id').value;
  $('#btn-filtrar').disabled = v;
};

const SELF_ID = <?php echo (int)$SELF_ID; ?>;

const post = async (data) => {
  const fd = new FormData();
  for (const k in data) fd.append(k, data[k] ?? '');
  fd.append('ajax', '1');
  fd.append('csrf', $('#csrf').value);

  const res  = await fetch(location.href, { method: 'POST', body: fd });
  const text = await res.text();

  console.log('STATUS:', res.status);
  console.log('RESPUESTA CRUDA:', text);

  try { 
    return JSON.parse(text); 
  } catch (e) {
    console.error('Error parseando JSON:', e);
    return { ok:false, msg:'Respuesta no JSON: ' + text, status:res.status, raw:text };
  }
};

function validarForm({edicion = false} = {}){
  const u  = $('#usuario').value.trim();
  const nc = $('#nombre').value.trim();
  const r  = $('#rol').value;
  const fi = $('#fecha_ini').value;
  const ff = $('#fecha_fin').value;
  const p1 = $('#password').value;
  const p2 = $('#password2').value;

  if (!u) return 'El usuario es obligatorio.';
  if (!nc) return 'El nombre completo es obligatorio.';
  if (!r || Number(r) <= 0) return 'Selecciona un rol.';
  if (fi && ff && new Date(fi) > new Date(ff)) return 'La fecha final no puede ser menor que la de inicio.';

  if (!edicion) {
    if (!p1 || !p2) return 'Ingresa y confirma la contraseña.';
    if (p1 !== p2) return 'Las contraseñas no coinciden.';
    if (p1.length < 8) return 'La contraseña debe tener al menos 8 caracteres.';
  } else {
    if (!p1) return 'Escribe la contraseña actual para confirmar los cambios.';
  }
  return '';
}

async function cargarRoles(){
  const r = await post({action:'roles'});
  if (!r.ok) {
    setMsg(r.msg, false);
    return;
  }

  const rolSel = $('#rol');
  rolSel.innerHTML = '';

  r.data.forEach(x => {
    const opt = document.createElement('option');
    opt.value = x.id;
    opt.textContent = x.nombre;
    rolSel.appendChild(opt);
  });

  const frol = $('#f-rol');
  r.data.forEach(x => {
    const opt = document.createElement('option');
    opt.value = x.id;
    opt.textContent = x.nombre;
    frol.appendChild(opt);
  });
}

async function listar(){
  busy(true);
  const r = await post({
    action:  'list',
    buscar:  $('#f-buscar').value.trim(),
    rol_id:  $('#f-rol').value,
    estado:  $('#f-estado').value
  });
  busy(false);

  const tb = $('#tbody');
  tb.innerHTML = '';

  if (!r.ok) {
    setMsg(r.msg, false);
    return;
  }
  if (!r.data.length) {
    tb.innerHTML = '<tr><td colspan="5" style="text-align:center;opacity:.7;">Sin resultados</td></tr>';
    return;
  }

  r.data.forEach(x => {
    const esYo = Number(x.CvUsuario) === SELF_ID;
    const tr   = document.createElement('tr');
    tr.innerHTML = `
      <td>${x.Usuario ?? x.Nombre ?? ''}</td>
      <td>${x.NombreCompleto ?? ''}</td>
      <td>${x.Rol ?? ''}</td>
      <td>${x.Estado ?? ''}</td>
      <td>
        <button class="btn btn-chip btn-edit" data-id="${x.CvUsuario}" ${esYo ? 'disabled' : ''}>Editar</button>
        <span class="sep">|</span>
        <button class="btn btn-chip danger btn-del" data-id="${x.CvUsuario}" ${esYo ? 'disabled' : ''}>Borrar</button>
      </td>
    `;
    tb.appendChild(tr);
  });

  $$('.btn-edit:not([disabled])').forEach(b =>
    b.addEventListener('click', () => cargarEnFormulario(b.dataset.id))
  );
  $$('.btn-del:not([disabled])').forEach(b =>
    b.addEventListener('click', () => borrar(b.dataset.id))
  );
}

async function cargarEnFormulario(id){
  const r = await post({action:'get', id});
  if (!r.ok) {
    setMsg(r.msg, false);
    return;
  }

  const u = r.data;
  $('#id').value         = u.CvUsuario;
  $('#usuario').value    = u.Usuario || u.Nombre || '';
  $('#nombre').value     = u.NombreCompleto ?? '';
  $('#estado').value     = u.Estado ?? 'Activo';
  $('#rol').value        = u.CvTipPersona ?? '';
  $('#fecha_ini').value  = u.FechaInicio ?? '';
  $('#fecha_fin').value  = u.FechaFin ?? '';
  $('#password').value   = '';
  $('#password2').value  = '';

  $('#btn-editar').disabled = false;
  setMsg('Listo para editar.', true);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function limpiar(){
  $('#id').value = '';
  $('#usuario').value = '';
  $('#nombre').value  = '';
  $('#estado').value  = 'Activo';
  $('#rol').selectedIndex = 0;
  $('#password').value  = '';
  $('#password2').value = '';
  $('#fecha_ini').value = '';
  $('#fecha_fin').value = '';
  $('#btn-editar').disabled = true;
}

async function guardar(){
  const err = validarForm({edicion:false});
  if (err) {
    setMsg(err, false);
    return;
  }

  const rolVal = $('#rol').value;
  if (!rolVal || isNaN(rolVal) || Number(rolVal) <= 0) {
    setMsg('⚠️ Rol no válido. Intenta recargar la página.', false);
    return;
  }

  busy(true);
  const r = await post({
    action:   'create',
    usuario:  $('#usuario').value.trim(),
    nombre:   $('#nombre').value.trim(),
    estado:   $('#estado').value,
    rol_id:   $('#rol').value,
    password: $('#password').value,
    password2:$('#password2').value,
    fecha_ini:$('#fecha_ini').value,
    fecha_fin:$('#fecha_fin').value
  });
  busy(false);

  if (r.ok) {
    setMsg('Guardado correctamente');
    limpiar();
    listar();
  } else {
    setMsg(r.msg, false);
  }
}

async function editar(){
  if (!$('#id').value) {
    setMsg('No hay registro cargado', false);
    return;
  }
  const err = validarForm({edicion:true});
  if (err) {
    setMsg(err, false);
    return;
  }
  if (!confirm('¿Guardar los cambios?')) return;

  const r = await post({
    action:   'update',
    id:       $('#id').value,
    usuario:  $('#usuario').value.trim(),
    nombre:   $('#nombre').value.trim(),
    estado:   $('#estado').value,
    rol_id:   $('#rol').value,
    password: $('#password').value,
    fecha_ini:$('#fecha_ini').value,
    fecha_fin:$('#fecha_fin').value
  });

  if (r.ok) {
    setMsg('Actualizado');
    limpiar();
    listar();
  } else {
    setMsg(r.msg, false);
  }
}

async function borrar(id){
  if (!confirm('¿Eliminar este usuario?')) return;
  const r = await post({action:'delete', id});
  if (r.ok) {
    setMsg('Eliminado');
    listar();
  } else {
    setMsg(r.msg, false);
  }
}

$('#btn-filtrar').addEventListener('click', listar);
$('#f-buscar').addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    listar();
  }
});
$('#btn-nuevo').addEventListener('click', limpiar);
$('#btn-cancelar').addEventListener('click', limpiar);
$('#btn-guardar').addEventListener('click', guardar);
$('#btn-editar').addEventListener('click', editar);

(async function init(){
  await cargarRoles();
  await listar();
})();
</script>
</body>
</html>
