<?php
session_set_cookie_params([
  'path' => '/',
  'httponly' => true,
  'samesite' => 'Lax'
]);
// /Login/procesa_login.php
session_start();
require_once __DIR__ . '/conexion.php';

// ---------- 1) Entradas ----------
$usuario  = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

if ($usuario === '' || $password === '') {
    header('Location: login.php?e=campos');
    exit;
}

// ---------- 2) Traer usuario + datos ----------
$sql = "
SELECT 
  u.CvUsuario,
  u.Nombre              AS username,
  u.Password            AS pass,
  u.CvPerson,
  u.NombreCompleto      AS nombre_completo_bd,
  u.CvTipPersona        AS rol_id,
  u.Estado              AS estado,
  u.foto                AS foto,         -- ✔ FOTO REAL DE LA TABLA
  tp.DsTipPersona       AS rol_nombre,

  n.DsNombre            AS nombre,
  ap1.DsApellido        AS ape_pat,
  ap2.DsApellido        AS ape_mat
FROM musuarios u
LEFT JOIN mdatperson p   ON p.CvPerson = u.CvPerson
LEFT JOIN cnombre n      ON n.CvNombre = p.CvNombre
LEFT JOIN capellido ap1  ON ap1.CvApellido = p.CvApePat
LEFT JOIN capellido ap2  ON ap2.CvApellido = p.CvApeMat
LEFT JOIN ctippersona tp ON tp.CvTipPersona = u.CvTipPersona
WHERE u.Nombre = :u
LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([':u' => $usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: login.php?e=credenciales');
    exit;
}

// ---------- 3) Validar estado ----------
if (isset($user['estado']) && strtolower($user['estado']) === 'inactivo') {
    header('Location: login.php?e=inactivo');
    exit;
}

// ---------- 4) Validar contraseña ----------
$hashDB  = $user['pass'] ?? '';
$usaHash = strlen($hashDB) > 20;
$okPass  = $usaHash ? password_verify($password, $hashDB) : hash_equals($hashDB, $password);

if (!$okPass) {
    header('Location: login.php?e=credenciales');
    exit;
}

// ---------- 5) Resolver nombre completo ----------
$partes = array_filter([
    $user['nombre']  ?? '',
    $user['ape_pat'] ?? '',
    $user['ape_mat'] ?? ''
], fn($v) => trim($v) !== '');

if (!empty($user['nombre_completo_bd'])) {
    $fullName = trim($user['nombre_completo_bd']);
} elseif (!empty($partes)) {
    $fullName = trim(implode(' ', $partes));
} else {
    $fullName = (string)($user['username'] ?? 'Invitado');
}
$fullNameFmt = mb_convert_case($fullName, MB_CASE_TITLE, 'UTF-8');

// ---------- 6) Guardar sesión ----------
session_regenerate_id(true);
$_SESSION['usuario'] = [
    'id'              => (int)$user['CvUsuario'],
    'username'        => $user['username'],
    'nombre_completo' => $fullNameFmt,
    'foto'            => $user['foto'] ?? 'default.png',
    'rol'             => $user['rol_nombre'],     // TEXTO: Administrador, Vigilante
    'CvTipPersona'    => (int)$user['rol_id'],    // ID: 1, 2, 3
];


// ---------- 7) Actualizar último acceso ----------
try {
    $upd = $conn->prepare("UPDATE mUsuarios SET UltimoAcceso = NOW() WHERE CvUsuario = ?");
    $upd->execute([(int)$user['CvUsuario']]);
} catch (Throwable $e) {}

// ---------- 8) Redirigir ----------
header('Location: ../Menu/menu.php');
exit;
