<?php
// /Login/procesa_reset.php
session_start();
require_once __DIR__ . '/../Login/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: recuperar.php?e=campos'); exit;
}

/* CSRF */
if (empty($_POST['csrf']) || empty($_SESSION['csrf_reset']) || !hash_equals($_SESSION['csrf_reset'], $_POST['csrf'])) {
  header('Location: recuperar.php?e=csrf'); exit;
}

$token = trim($_POST['token'] ?? '');
$pass1 = (string)($_POST['pass1'] ?? '');
$pass2 = (string)($_POST['pass2'] ?? '');

if ($token === '' || $pass1 === '' || $pass2 === '') {
  header('Location: recuperar.php?e=campos'); exit;
}

if ($pass1 !== $pass2) {
  header('Location: recuperar.php?e=campos'); exit;
}

/* Reglas mínimas de contraseña */
if (strlen($pass1) < 8) {
  header('Location: recuperar.php?e=campos'); exit;
}

$tokenHash = hash('sha256', $token);

try {
  // Validar token
  $st = $conn->prepare("
    SELECT pr.id, pr.CvUsuario, pr.expira, pr.usado
    FROM password_resets pr
    WHERE pr.token_hash = :th
    LIMIT 1
  ");
  $st->execute([':th' => $tokenHash]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    header('Location: recuperar.php?e=token'); exit;
  }

  if ((int)$row['usado'] === 1 || strtotime($row['expira']) <= time()) {
    header('Location: recuperar.php?e=token'); exit;
  }

  $idUsuario = (int)$row['CvUsuario'];
  $hashPass  = password_hash($pass1, PASSWORD_DEFAULT);

  // Actualiza contraseña (AJUSTA el nombre del campo si no es "password")
  $up = $conn->prepare("UPDATE mUsuarios SET password = :p WHERE CvUsuario = :id LIMIT 1");
  $up->execute([':p' => $hashPass, ':id' => $idUsuario]);

  // Marca token como usado
  $mk = $conn->prepare("UPDATE password_resets SET usado = 1 WHERE id = :rid LIMIT 1");
  $mk->execute([':rid' => (int)$row['id']]);

  // Limpia CSRF reset para que no reusen el POST
  unset($_SESSION['csrf_reset']);

  // Envía a login con mensaje
  header('Location: login.php?reset=1'); exit;

} catch (Throwable $e) {
  header('Location: recuperar.php?e=token'); exit;
}
