<?php
// /Login/recuperar.php
session_start();

// Si ya hay sesión activa, manda a menú
if (!empty($_SESSION['usuario'])) {
  header('Location: ../Menu/menu.php');
  exit;
}

// CSRF token simple para el formulario
if (empty($_SESSION['csrf_rec'])) {
  $_SESSION['csrf_rec'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_rec'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Recuperar contraseña</title>
  <link rel="stylesheet" href="recuperar.css" />
</head>
<body>

<div class="wrap">
  <!-- Columna izquierda (blanca) -->
  <aside class="left">
    <div class="left-inner">
      <div class="brand-stack">
        <img src="../Multimedia/Logo_CONALEP_Secundario.png" alt="Orgullosamente CONALEP" class="logo-left">
        <div class="brand-name"></div>
      </div>

      <div class="subline">Chiapas</div>
      <div class="location">Plantel CONALEP 070.</div>
      <div class="campus">Comitán</div>

      <div class="social" aria-label="Redes sociales">
        <!-- Facebook -->
        <a href="https://www.facebook.com/Conalep070?locale=es_LA"
           title="Facebook oficial CONALEP 070"
           aria-label="Facebook oficial CONALEP 070"
           target="_blank" rel="noopener noreferrer">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="#0B4D3D" aria-hidden="true">
            <path d="M22 12a10 10 0 1 0-11.6 9.9v-7h-2.4V12h2.4V9.7c0-2.4 1.4-3.7 3.6-3.7 1 0 2 .2 2 .2v2.2h-1.1c-1.1 0-1.5.7-1.5 1.4V12h2.6l-.4 2.9h-2.2v7A10 10 0 0 0 22 12z"/>
          </svg>
        </a>

        <!-- Instagram -->
        <a href="https://www.instagram.com/conalepcomitan070/"
           title="Instagram oficial CONALEP 070"
           aria-label="Instagram oficial CONALEP 070"
           target="_blank" rel="noopener noreferrer">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="#0B4D3D" aria-hidden="true">
            <path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.9.3 2.4.5.6.2 1 .6 1.5 1.1.5.5.9.9 1.1 1.5.2.5.4 1.2.5 2.4.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.9-.5 2.4-.2.6-.6 1-1.1 1.5-.5.5-.9.9-1.5 1.1-.5.2-1.2.4-2.4.5-1.3.1-1.7.1-4.9.1s-3.5 0-4.8-.1c-1.2-.1-1.9-.3-2.4-.5-.6-.2-1-.6-1.5-1.1-.5-.5-.9-.9-1.1-1.5-.2-.5-.4-1.2-.5-2.4C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.3-1.9.5-2.4.2-.6.6-1 1.1-1.5.5-.5.9-.9 1.5-1.1.5-.2 1.2-.4 2.4-.5C8.4 2.2 8.8 2.2 12 2.2z"/>
          </svg>
        </a>
      </div>
    </div>
  </aside>

  <!-- Columna derecha (verde) -->
  <main class="right">
<!-- LOGO SUPERIOR IZQUIERDO -->
<div class="corner-brand left-align">
  <img src="../Multimedia/Logo_CONALEP_horizontal.png" alt="CONALEP Chiapas">
</div>


    <!-- Mensajes -->
    <?php if (isset($_GET['ok'])): ?>
      <div class="alert success">Si la cuenta existe, te enviamos instrucciones a tu correo.</div>
    <?php elseif (isset($_GET['e'])): ?>
      <div class="alert">
        <?php
          $msg = $_GET['e'] === 'campos' ? 'Ingresa tu usuario o correo.'
               : 'No fue posible procesar tu solicitud. Intenta de nuevo.';
          echo htmlspecialchars($msg);
        ?>
      </div>
    <?php endif; ?>

    <!-- Título -->
    <h1 class="welcome">Recuperar contraseña</h1>
    <p class="subtitle">Escribe tu <strong>usuario</strong> o <strong>correo institucional</strong> y te enviaremos un enlace para restablecer tu contraseña.</p>

    <!-- FORMULARIO -->
    <form class="card" method="POST" action="procesa_recuperar.php" autocomplete="off">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

      <div class="form-row">
        <label for="cuenta">Correo electronico:</label>
        <input class="input" type="text" id="cuenta" name="cuenta" placeholder="correo@conalep.edu.mx" required />
      </div>

      <div class="form-row">
        <label for="captcha">Verificación (opcional):</label>
        <input class="input" type="text" id="captcha" name="captcha" placeholder="Escribe CONALEP" />
      </div>

      <div class="actions">
        <button class="btn" type="submit">Enviar enlace</button>
      </div>

      <div class="links">
        <a href="../Login/login.php" class="back-link">Volver a iniciar sesión</a>
      </div>
    </form>

    <!-- Pie -->
    <div class="footer">
      © All Right Reserved. Designed by
      <a href="#">CONALEP Comitán Clave 070</a>
    </div>
  </main>
</div>

</body>
</html>
