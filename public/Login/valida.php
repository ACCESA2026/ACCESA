<?php
// /Login/valida.php
session_start();
require_once __DIR__ . '/conexion.php';

$user = trim($_POST['usuario'] ?? '');
$pass = $_POST['password'] ?? '';

if ($user === '' || $pass === '') {
  header('Location: login.php?err=empty'); exit;
}

$sql = "SELECT CvUsuario, Nombre, NombreCompleto, Email, Password, Estado, CvTipPersona
        FROM mUsuarios
        WHERE Nombre = :u
        LIMIT 1";
$st = $conn->prepare($sql);
$st->execute([':u'=>$user]);
$row = $st->fetch();

if (!$row) { header('Location: login.php?err=notfound'); exit; }
if ($row['Estado'] !== 'Activo') { header('Location: login.php?err=inactivo'); exit; }

if (!password_verify($pass, $row['Password'])) {
  header('Location: login.php?err=badpass'); exit;
}

// Supongamos que $u es el registro de mUsuarios
// después de verificar password, $u es el row de mUsuarios
$_SESSION['usuario'] = [
  'id'              => $u['CvUsuario'],
  'username'        => $u['Nombre'],                   // usuario
  'nombre_completo' => $u['NombreCompleto'] ?: $u['Nombre'], // <— clave
  'rol_id'          => $u['CvTipPersona'],
  // ... lo que uses
];


$up = $conn->prepare("UPDATE mUsuarios SET UltimoAcceso = NOW() WHERE CvUsuario = ?");
$up->execute([ (int)$row['CvUsuario'] ]);

header('Location: ../Menu/menu.php');
exit;
