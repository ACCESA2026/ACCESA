<?php
// /Login/reset.php
session_start();
require_once __DIR__ . '/../Login/conexion.php';

$token = $_GET['token'] ?? '';
$token = trim($token);

if ($token === '') {
  header('Location: recuperar.php?e=token'); exit;
}

if (empty($_SESSION['csrf_reset'])) {
  $_SESSION['csrf_reset'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_reset'];

$tokenHash = hash('sha256', $token);

try {
  $st = $conn->prepare("
    SELECT pr.id, pr.CvUsuario, pr.expira, pr.usado
    FROM password_resets pr
    WHERE pr.token_hash = :th
    LIMIT 1
  ");
  $st->execute([':th' => $tokenHash]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $row = false;
}

$valido = false;
if ($row) {
  if ((int)$row['usado'] === 0 && strtotime($row['expira']) > time()) {
    $valido = true;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Restablecer contraseña</title>
  <link rel="stylesheet" href="recuperar.css" />
</head>
<body>

<div class="wrap">
  <!-- Columna izquierda -->
  <aside class="left">
    <div class="left-inner">
      <div class="brand-stack">
        <img src="../Multimedia/Logo_CONALEP_Secundario.png" alt="Orgullosamente CONALEP" class="logo-left">
      </div>
      <div class="subline">Chiapas</div>
      <div class="location">Plantel CONALEP 070.</div>
      <div class="campus">Comitán</div>
    </div>
  </aside>

  <!-- Columna derecha -->
  <main class="right">
    <div class="corner-brand left-align">
      <img src="../Multimedia/Logo_CONALEP_horizontal.png" alt="CONALEP Chiapas">
    </div>

    <h1 class="welcome">Restablecer contraseña</h1>
    <p class="subtitle">Crea una nueva contraseña para tu cuenta.</p>

    <?php if (!$valido): ?>
      <div class="alert">El enlace es inválido o ya expiró. Solicita uno nuevo.</div>

      <div class="links">
        <a href="recuperar.php" class="back-link">Volver a recuperación</a>
      </div>

    <?php else: ?>
      <form class="card" method="POST" action="procesa_reset.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="form-row">
          <label for="pass1">Nueva contraseña:</label>
          <input class="input" type="password" id="pass1" name="pass1" placeholder="Mínimo 8 caracteres" required />
        </div>

        <div class="form-row">
          <label for="pass2">Confirmar contraseña:</label>
          <input class="input" type="password" id="pass2" name="pass2" placeholder="Repite tu contraseña" required />
        </div>

        <div class="actions">
          <button class="btn" type="submit">Guardar contraseña</button>
        </div>

        <div class="links">
          <a href="login.php" class="back-link">Volver a iniciar sesión</a>
        </div>
      </form>
    <?php endif; ?>

    <div class="footer">
      © All Right Reserved. Designed by
      <a href="#">CONALEP Comitán Clave 070</a>
    </div>
  </main>
</div>

</body>
</html>
