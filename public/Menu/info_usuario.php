<?php
session_start();
require_once "../Login/conexion.php";

/* =========================
   0) Verificar sesión
========================= */
if (empty($_SESSION['usuario'])) {
    header("Location: ../Login/login.php");
    exit;
}

/* =========================
   1) Helpers DB (PDO o MySQLi)
========================= */
function is_pdo($c){ return $c instanceof PDO; }
function is_mysqli($c){ return $c instanceof mysqli; }

function db_fetch_one($conn, $sql, $types, $params){
    if (is_pdo($conn)) {
        $st = $conn->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
    if (is_mysqli($conn)) {
        $st = $conn->prepare($sql);
        if (!$st) return null;
        if ($types !== '' && $params) {
            $st->bind_param($types, ...$params);
        }
        $st->execute();
        $res = $st->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $st->close();
        return $row ?: null;
    }
    return null;
}

function db_exec($conn, $sql, $types, $params){
    if (is_pdo($conn)) {
        $st = $conn->prepare($sql);
        return $st->execute($params);
    }
    if (is_mysqli($conn)) {
        $st = $conn->prepare($sql);
        if (!$st) return false;
        if ($types !== '' && $params) {
            $st->bind_param($types, ...$params);
        }
        $ok = $st->execute();
        $st->close();
        return $ok;
    }
    return false;
}

/* =========================
   2) Datos del usuario (desde sesión)
========================= */
// Tu sesión usa 'id', no 'CvUsuario'
$idUsuario = (int)($_SESSION['usuario']['id'] ?? 0);

$nombreCompleto = $_SESSION['usuario']['nombre_completo'] ?? "Desconocido";
$usuario        = $_SESSION['usuario']['username'] ?? "N/A";
$rol            = $_SESSION['usuario']['rol'] ?? "Sin rol asignado";
$fotoPerfil     = $_SESSION['usuario']['foto'] ?? 'default.png';

$nombreCompletoFmt = mb_convert_case($nombreCompleto, MB_CASE_TITLE, 'UTF-8');
$error = '';
$success = '';

/* =========================
   3) Rutas (InfinityFree)
========================= */
$rutaCarpeta = __DIR__ . "/fotos_perfil/";
$urlCarpeta  = "https://accesa.infinityfree.me/Menu/fotos_perfil/";

if (!file_exists($rutaCarpeta)) {
    @mkdir($rutaCarpeta, 0755, true);
}

/* =========================
   4) Sincronizar foto desde BD al cargar (evita sesión desactualizada)
========================= */
try {
    $row = db_fetch_one(
        $conn,
        "SELECT Nombre, IFNULL(NULLIF(NombreCompleto,''),Nombre) AS NombreCompleto, foto, Estado, CvTipPersona
         FROM musuarios
         WHERE CvUsuario = ? LIMIT 1",
        "i",
        [$idUsuario]
    );

    if ($row) {
        $_SESSION['usuario']['nombre_completo'] = $row['NombreCompleto'] ?: $nombreCompleto;
        $_SESSION['usuario']['username'] = $row['Nombre'] ?: $usuario;
        $_SESSION['usuario']['foto'] = $row['foto'] ?: 'default.png';
        $fotoPerfil = $_SESSION['usuario']['foto'];
    }
} catch (Throwable $e) {
    // no rompemos la página si falla
}

/* =========================
   5) Eliminar foto
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminarFoto"])) {
    $rutaActual = $rutaCarpeta . $fotoPerfil;
    if ($fotoPerfil !== 'default.png' && file_exists($rutaActual)) {
        @unlink($rutaActual);
    }

    $ok = db_exec($conn, "UPDATE musuarios SET foto='default.png' WHERE CvUsuario=?", "i", [$idUsuario]);
    if ($ok) {
        $_SESSION['usuario']['foto'] = 'default.png';
        $fotoPerfil = 'default.png';
        $success = "Foto eliminada correctamente.";
    } else {
        $error = "No se pudo actualizar la foto en la base de datos.";
    }
}

/* =========================
   6) Subir foto (Cropper / archivo normal)
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["fotoPerfil"]) && $_FILES["fotoPerfil"]["error"] === 0) {
    $archivo = $_FILES["fotoPerfil"];
    $tmp = $archivo["tmp_name"];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($archivo["size"] > $maxSize) {
        $error = "La imagen supera el tamaño máximo permitido (2 MB).";
    }

    $mime = mime_content_type($tmp);
    $permitidos = ["image/jpeg", "image/png", "image/jpg"];
    if (!in_array($mime, $permitidos)) {
        $error = "Solo se permiten imágenes JPG o PNG.";
    }

    if (empty($error)) {
        $nuevoNombre = "perfil_" . $idUsuario . "_" . time() . ".png";
        $rutaDestino = $rutaCarpeta . $nuevoNombre;

        if ($fotoPerfil !== 'default.png' && file_exists($rutaCarpeta . $fotoPerfil)) {
            @unlink($rutaCarpeta . $fotoPerfil);
        }

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $img = imagecreatefromjpeg($tmp);
            imagepng($img, $rutaDestino, 6);
            imagedestroy($img);
        } else {
            move_uploaded_file($tmp, $rutaDestino);
        }

        $ok = db_exec($conn, "UPDATE musuarios SET foto=? WHERE CvUsuario=?", "si", [$nuevoNombre, $idUsuario]);
        if ($ok) {
            $_SESSION['usuario']['foto'] = $nuevoNombre;
            $fotoPerfil = $nuevoNombre;
            $success = "Foto actualizada correctamente.";
        } else {
            $error = "No se pudo actualizar la foto en la base de datos.";
        }
    }
}

/* =========================
   7) Cache buster
========================= */
$cacheBuster = file_exists($rutaCarpeta . $fotoPerfil) ? filemtime($rutaCarpeta . $fotoPerfil) : time();

$menuHref = (file_exists(__DIR__ . "/menu.php")) ? "menu.php" : "../Menu/menu.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Información del usuario</title>
    <link rel="stylesheet" href="info_usuario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
</head>
<body>

<?php include "../Centro_logo/header.php"; ?>

<main class="info-container">
  <div class="info-card">
    <h2>Información del usuario</h2>

    <div class="foto-section">
      <img src="<?php echo $urlCarpeta . htmlspecialchars($fotoPerfil); ?>?v=<?php echo $cacheBuster; ?>"
           class="foto-perfil" alt="Foto de perfil">

      <div class="acciones-foto">
        <form onsubmit="return false;">
          <label class="btn-subir">
            Cambiar foto
            <input type="file" name="fotoPerfil" accept="image/*" onchange="abrirEditor(this)">
          </label>
        </form>

        <?php if ($fotoPerfil !== 'default.png'): ?>
          <form method="POST" onsubmit="return confirmarEliminacion();">
            <button type="submit" name="eliminarFoto" class="btn-eliminar">🗑 Eliminar foto</button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (!empty($error)): ?><p class="msg error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
      <?php if (!empty($success)): ?><p class="msg success"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
    </div>

    <div class="info-row">
      <span class="label">Nombre completo:</span>
      <span class="value"><?php echo htmlspecialchars($nombreCompletoFmt); ?></span>
    </div>

    <div class="info-row">
      <span class="label">Usuario:</span>
      <span class="value"><?php echo htmlspecialchars($usuario); ?></span>
    </div>

    <div class="info-row">
      <span class="label">Rol asignado:</span>
      <span class="value rol"><?php echo htmlspecialchars($rol); ?></span>
    </div>

    <a href="<?php echo $menuHref; ?>" class="btn-volver">Volver al menú</a>
  </div>
</main>

<div id="modalFoto" class="modal-foto" style="display:none;">
  <div class="modal-contenido">
    <h3>Ajustar foto de perfil</h3>
    <div class="crop-container"><img id="imagenCrop" alt="Recorte"></div>
    <div class="acciones-modal">
      <button class="btn-cancelar" type="button" onclick="cerrarModal()">Cancelar</button>
      <button class="btn-aceptar" type="button" onclick="guardarFoto()">Aceptar</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
function confirmarEliminacion(){return confirm("¿Estás seguro de eliminar tu foto de perfil?");}
let cropper=null;
function abrirEditor(input){
  const file=input.files[0];
  if(!file)return;
  const reader=new FileReader();
  reader.onload=e=>{
    const img=document.getElementById("imagenCrop");
    img.src=e.target.result;
    document.getElementById("modalFoto").style.display="flex";
    document.body.style.overflow="hidden";
    img.onload=()=>{cropper=new Cropper(img,{aspectRatio:1,viewMode:1,autoCropArea:1,background:false});};
  };
  reader.readAsDataURL(file);
}
function cerrarModal(){if(cropper){cropper.destroy();cropper=null;}document.getElementById("modalFoto").style.display="none";document.body.style.overflow="";}
function guardarFoto(){
  if(!cropper)return;
  cropper.getCroppedCanvas({width:400,height:400,imageSmoothingQuality:'high'}).toBlob(blob=>{
    const fd=new FormData();fd.append("fotoPerfil",blob,"perfil.png");
    fetch(window.location.href,{method:"POST",body:fd}).then(()=>location.reload());
  },"image/png");
}
</script>
</body>
</html>
