<?php
// /Olvidar_Contrasena/procesa_recuperar.php
session_start();
require_once __DIR__ . '/../Login/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
$mail->Username = 'tucorreo@gmail.com';
$mail->Password = 'abcd efgh ijkl mnop'; // contraseña de aplicación

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('TU_CORREO@gmail.com', 'CONALEP Comitán');
    $mail->addAddress($user['correo']);

    $mail->isHTML(true);
    $mail->Subject = 'Recuperar contraseña - CONALEP';
    $mail->Body = "
        <p>Hola,</p>
        <p>Solicitaste restablecer tu contraseña.</p>
        <p><a href='$link'>Haz clic aquí para continuar</a></p>
        <p><strong>Este enlace vence en 15 minutos.</strong></p>
    ";

    $mail->send();
} catch (Exception $e) {
    // No mostramos errores por seguridad
}

header('Location: recuperar.php?ok=1');
exit;


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: recuperar.php?e=campos'); 
  exit;
}

/* ================= CSRF ================= */
if (
  empty($_POST['csrf']) ||
  empty($_SESSION['csrf_rec']) ||
  !hash_equals($_SESSION['csrf_rec'], $_POST['csrf'])
) {
  header('Location: recuperar.php?e=csrf'); 
  exit;
}

/* ================= Inputs ================= */
$cuenta  = trim($_POST['cuenta'] ?? '');
$captcha = strtoupper(trim($_POST['captcha'] ?? ''));

if ($cuenta === '') {
  header('Location: recuperar.php?e=campos'); 
  exit;
}

/* ================= Captcha manual ================= */
if ($captcha !== '' && $captcha !== 'CONALEP') {
  header('Location: recuperar.php?e=campos'); 
  exit;
}

/* ================= Validación correo ================= */
if (str_contains($cuenta, '@') && !filter_var($cuenta, FILTER_VALIDATE_EMAIL)) {
  header('Location: recuperar.php?e=campos'); 
  exit;
}

/* ================= Buscar usuario ================= */
try {
  $st = $conn->prepare("
    SELECT CvUsuario, correo, username
    FROM mUsuarios
    WHERE correo = :c OR username = :c
    LIMIT 1
  ");
  $st->execute([':c' => $cuenta]);
  $user = $st->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  header('Location: recuperar.php?ok=1'); 
  exit;
}

/* ================= Si no existe ================= */
if (!$user) {
  header('Location: recuperar.php?ok=1'); 
  exit;
}

/* ================= Generar token ================= */
$token     = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expira    = date('Y-m-d H:i:s', strtotime('+15 minutes'));

try {
  // Invalida tokens previos
  $conn->prepare("
    UPDATE password_resets 
    SET usado = 1 
    WHERE CvUsuario = :id AND usado = 0
  ")->execute([':id' => (int)$user['CvUsuario']]);

  // Inserta nuevo token
  $conn->prepare("
    INSERT INTO password_resets (CvUsuario, token_hash, expira, usado)
    VALUES (:id, :th, :ex, 0)
  ")->execute([
    ':id' => (int)$user['CvUsuario'],
    ':th' => $tokenHash,
    ':ex' => $expira
  ]);

} catch (Throwable $e) {
  header('Location: recuperar.php?ok=1'); 
  exit;
}

/* ================= LINK DE PRUEBA (OPCIÓN 1) =================
   👉 SOLO PARA DESARROLLO
*/


/* ================= LINK DE PRUEBA ================= */
$baseUrl = "http://localhost/ACCESA%2014%20de%20octubre%202025/ACCESA/Olvidar_Contrasena";
$link = $baseUrl . "/reset.php?token=" . urlencode($token);


/* ================= MOSTRAR LINK ================= */
echo "<h2>LINK DE PRUEBA – RECUPERAR CONTRASEÑA</h2>";
echo "<p>Copia y pega este enlace en el navegador:</p>";
echo "<a href='$link' target='_blank'>$link</a>";
exit;
