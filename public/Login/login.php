<?php
session_start();


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <!-- TÍTULO (SOLO UNO) -->
    <title>CONALEP | ACCESA</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

    <!-- ESTILOS -->
    <link rel="stylesheet" href="../estilos/responsive.css">
    <link rel="stylesheet" href="login.css" />
</head>

<body>

<div class="wrap">
  <aside class="left">
    <div class="left-inner">
      <div class="brand-stack">
        <img src="../Multimedia/Logo_CONALEP_Secundario.png" alt="Orgullosamente CONALEP" class="logo-left">
      </div>
      <div class="subline">Chiapas</div>
      <div class="location">Plantel CONALEP 070.</div>
      <div class="campus">Comitán</div>

      <div class="social">
        <a href="https://www.facebook.com/Conalep070?locale=es_LA" target="_blank" rel="noopener">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="#0B4D3D">
            <path d="M22 12a10 10 0 1 0-11.6 9.9v-7h-2.4V12h2.4V9.7c0-2.4 1.4-3.7 3.6-3.7 1 0 2 .2 2 .2v2.2h-1.1c-1.1 0-1.5.7-1.5 1.4V12h2.6l-.4 2.9h-2.2v7A10 10 0 0 0 22 12z"/>
          </svg>
        </a>
        <a href="https://www.instagram.com/conalepcomitan070/" target="_blank" rel="noopener">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="#0B4D3D">
            <path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.9.3 2.4.5.6.2 1 .6 1.5 1.1.5.5.9.9 1.1 1.5.2.5.4 1.2.5 2.4.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.9-.5 2.4-.2.6-.6 1-1.1 1.5-.5.5-.9.9-1.5 1.1-.5.2-1.2.4-2.4.5-1.3.1-1.7.1-4.9.1s-3.5 0-4.8-.1c-1.2-.1-1.9-.3-2.4-.5-.6-.2-1-.6-1.5-1.1-.5-.5-.9-.9-1.1-1.5-.2-.5-.4-1.2-.5-2.4C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.3-1.9.5-2.4.2-.6.6-1 1.1-1.5.5-.5.9-.9 1.5-1.1.5-.2 1.2-.4 2.4-.5C8.4 2.2 8.8 2.2 12 2.2z"/>
          </svg>
        </a>
      </div>
    </div>
  </aside>

  <main class="right">
    <a class="corner-brand" href="#"><img src="../Multimedia/Logo_CONALEP_horizontal.png" alt="CONALEP Chiapas"></a>

    <?php if (isset($_GET['e'])): ?>
      <div class="alert">
        <?php
          $msg = match($_GET['e']) {
            'campos' => 'Completa usuario y contraseña.',
            'credenciales' => 'Usuario o contraseña incorrectos.',
            'inactivo' => 'Tu cuenta está inactiva. Contacta al administrador.',
            default => 'Inicia sesión para continuar.'
          };
          echo htmlspecialchars($msg);
        ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['out'])): ?>
      <div class="alert success">Has cerrado sesión correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET['reset'])): ?>
  <div class="alert success">Contraseña actualizada. Ya puedes iniciar sesión.</div>
<?php endif; ?>


    <h1 class="welcome">BIENVENIDO</h1>

    <form class="card" method="POST" action="procesa_login.php" autocomplete="off">
      <div class="form-row">
        <label for="usuario">Usuario:</label>
        <input class="input" type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required />
      </div>
      <div class="form-row">
        <label for="password">Contraseña:</label>
        <input class="input" type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required />
      </div>
      <div class="actions">
        <button class="btn" type="submit">Iniciar sesión</button>
      </div>
      <div class="links">
      <a href="../Olvidar_Contrasena/recuperar.php">¿Olvidaste tu contraseña?</a>
      </div>
    </form>

<div class="footer">
  © All Right Reserved. Designed by 
  <a href="https://conalepcomitan070.edu.mx/" target="_blank" rel="noopener noreferrer">
    CONALEP Comitán Clave 070
  </a>
</div>

  </main>
</div>

</body>
</html>
