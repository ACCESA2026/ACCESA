<?php
// Mostrar nombre completo y fecha actual
session_start();
$nombreCompleto = $_SESSION['usuario']['nombre_completo'] ?? ($_SESSION['usuario']['username'] ?? 'Invitado');
$nombreCompletoFmt = mb_convert_case($nombreCompleto, MB_CASE_TITLE, 'UTF-8');

date_default_timezone_set('America/Mexico_City');
$fechaHoyPHP = date('d/m/Y');
$fotoPerfil = $_SESSION['usuario']['foto'] ?? 'default.png'; // Foto por defecto

// Incluir la conexión a la base de datos
require_once '../Login/conexion.php';

// ===============================
// DATOS PROVENIENTES DEL ESCANEO
// ===============================
$scanValido    = ($_GET['scan'] ?? '') === '1';
$scanMatricula = $_GET['matricula'] ?? '';

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Control de accesos - Estudiantes</title>
  <link rel="stylesheet" href="control_acceso.css" />

  <!-- ==========================
       ✅ SOLO AGREGADO: ESTILOS DEL MODAL
       ========================== -->
  <style>
    .modal-alumno {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0,0,0,.55);
      z-index: 9999;
      padding: 18px;
    }
    /* ✅ Difuminado del fondo cuando el modal está activo */
body.blur-active header,
body.blur-active main,
body.blur-active footer {
  filter: blur(6px);
  pointer-events: none; /* evita clics mientras está activo */
  user-select: none;
}
    .modal-alumno.open { display: flex; }

    .modal-card {
      width: min(520px, 100%);
      background: #fff;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,.25);
      animation: modalIn .18s ease-out;
    }
    @keyframes modalIn {
      from { transform: translateY(10px); opacity: .6; }
      to   { transform: translateY(0); opacity: 1; }
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 16px;
      background: #0B4D3D;
      color: #fff;
    }
    .modal-head h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 700;
      letter-spacing: .2px;
    }
    .modal-close {
      appearance: none;
      border: 0;
      background: transparent;
      color: #fff;
      font-size: 22px;
      line-height: 1;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 10px;
    }
    .modal-close:hover { background: rgba(255,255,255,.12); }

    .modal-body {
      display: grid;
      grid-template-columns: 150px 1fr;
      gap: 14px;
      padding: 16px;
      align-items: start;
    }

    .modal-foto-wrap {
      width: 150px;
      height: 150px;
      border-radius: 16px;
      overflow: hidden;
      border: 2px solid #e6efec;
      background: #f3f6f5;
      display: flex;
      align-items: center;
      justify-content: center;
    }
.modal-foto-wrap img {
  width: 100%;
  height: 100%;
  object-fit: contain;   /* 🔑 evita recorte */
  background: #f3f6f5;   /* marco neutro */
}

    .modal-info {
      display: grid;
      gap: 10px;
    }

    .modal-row {
      display: grid;
      grid-template-columns: 120px 1fr;
      gap: 10px;
      align-items: center;
    }
    .modal-row label {
      font-weight: 700;
      color: #163a33;
      font-size: 13px;
    }
    .modal-row input {
      width: 100%;
      padding: 10px 10px;
      border-radius: 12px;
      border: 1px solid #d8e6e2;
      background: #f7fbfa;
      color: #0f2f28;
      outline: none;
      font-size: 14px;
    }

    @media (max-width: 520px){
      .modal-body { grid-template-columns: 1fr; }
      .modal-foto-wrap { width: 100%; height: 220px; }
      .modal-row { grid-template-columns: 1fr; }
      .modal-row label { margin-bottom: -4px; }
    }
  </style>
</head>

<body>

<header class="topbar">
  <div class="container topbar-row">
    <div class="brand">
      <img src="../Multimedia/Logo_CONALEP_horizontal.png" alt="CONALEP" class="brand-logo white-logo" />
    </div>

    <!-- Mostramos la foto de perfil y nombre completo desde header.php -->
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
    <div class="title-bar">
      <h1 class="title-pill">Control de accesos - Estudiantes</h1>

<!-- Resultado -->
<section class="card">
  <div class="card-subtitle-bar" style="display: flex; justify-content: space-between; align-items: center;">
    <h2 class="card-subtitle" style="margin: 0;">Resultado del escaneo:</h2>
    <a href="modo_fotografia.php" class="btn btn-modo-foto-elegante">
      <i class="fa-solid fa-camera"></i> Modo fotografía
    </a>
  </div>



      <form class="search-wrap" id="searchMatForm" autocomplete="off">
        <label for="searchMat" class="search-label">Matrícula</label>
        <div class="search-field">
          <input class="search-mat" type="search" id="searchMat" placeholder="CONA070-001">
          <button class="search-icon-btn" type="submit">
            <svg viewBox="0 0 24 24">
              <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.71.71l.27.28v.79L20 21.5 21.5 20l-6-6zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
          </button>
        </div>
      </form>
    </div>

    <!-- Resultado -->
    <section class="card">
      <div class="card-subtitle-bar">
  <h2 class="card-subtitle">Resultado del escaneo:</h2>
 
</div>

      <div class="scan-grid">

        <!-- Foto -->
        <div class="qr-box">
          <img id="fotoAlumno" class="foto-alumno" src="" alt="Foto del estudiante">
        </div>

        <!-- Datos -->
        <form class="form-col">
          <div class="row">
            <label>Matrícula:</label>
            <input id="matricula" type="text" readonly>
          </div>
          <div class="row">
            <label>Nombre Completo:</label>
            <input id="nombre" type="text" readonly>
          </div>
          <div class="row">
            <label>Grupo:</label>
            <input id="grupo" type="text" readonly>
          </div>
          <div class="row">
            <label>Carrera:</label>
            <input id="carrera" type="text" readonly>
          </div>
          <div class="row">
            <label>Hora de acceso:</label>
            <input id="hora" type="text" readonly>
          </div>
          <div class="row">
            <label>Estado:</label>
            <input id="estado" type="text" readonly>
          </div>
        </form>
      </div>

      <div class="actions-center">
        <button id="btnAcceso" class="btn btn-allow hidden" type="button">✓ Acceso permitido</button>
      </div>

    </section>

    <!-- Historial -->
    <section class="card card-thin">
      <h3 class="card-mini-title">Historia de accesos recientes</h3>
      <div class="history">
        <div class="hr-line"></div>
        <table class="mini-table">
          <thead>
            <tr>
              <th>Hora</th>
              <th>Matrícula</th>
              <th>Nombre</th>
              <th>Grupo</th>
              <th>Resultados</th>
            </tr>
          </thead>
          <tbody id="historialBody"></tbody>
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

<!-- ==========================
     ✅ SOLO AGREGADO: MODAL DEL ALUMNO
     ========================== -->
<div id="modalAlumno" class="modal-alumno" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalAlumnoTitle">
    <div class="modal-head">
      <h3 id="modalAlumnoTitle">Datos del estudiante</h3>
      <button class="modal-close" type="button" id="btnCerrarModal" aria-label="Cerrar">×</button>
    </div>

    <div class="modal-body">
      <div class="modal-foto-wrap">
        <img id="modalFoto" src="" alt="Foto del estudiante">
      </div>

      <div class="modal-info">
        <div class="modal-row">
          <label>Matrícula:</label>
          <input id="modalMat" type="text" readonly>
        </div>
        <div class="modal-row">
          <label>Nombre:</label>
          <input id="modalNombre" type="text" readonly>
        </div>
        <div class="modal-row">
          <label>Grupo:</label>
          <input id="modalGrupo" type="text" readonly>
        </div>
        <div class="modal-row">
          <label>Carrera:</label>
          <input id="modalCarrera" type="text" readonly>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================== SCRIPT ===================== -->
<script>
(function () {

    const form      = document.getElementById('searchMatForm');
    const input     = document.getElementById('searchMat');
    const btnAcceso = document.getElementById('btnAcceso');

    const fMat     = document.getElementById('matricula');
    const fNombre  = document.getElementById('nombre');
    const fGrupo   = document.getElementById('grupo');
    const fCarrera = document.getElementById('carrera');
    const fHora    = document.getElementById('hora');
    const fEstado  = document.getElementById('estado');

    const fotoAlumno = document.getElementById("fotoAlumno");

    const listaSugerencias = document.createElement("ul");
    listaSugerencias.className = "sugerencias";
    input.parentNode.appendChild(listaSugerencias);
    input.addEventListener("input", function () {

  const valor = input.value.trim();

  listaSugerencias.innerHTML = "";
  listaSugerencias.style.display = "none";

  if (valor.length < 2) return;

  fetch("buscar_sugerencias.php?q=" + encodeURIComponent(valor))
    .then(res => res.json())
    .then(data => {

      if (!Array.isArray(data) || data.length === 0) return;

      data.forEach(item => {
        const li = document.createElement("li");
        li.textContent = `${item.Matricula} — ${item.nombre}`;

        li.addEventListener("click", () => {
          input.value = item.Matricula;
          listaSugerencias.innerHTML = "";
          listaSugerencias.style.display = "none";
          obtenerAlumno(item.Matricula, false);
        });

        listaSugerencias.appendChild(li);
      });

      listaSugerencias.style.display = "block";
    });
});
document.addEventListener("click", (e) => {
  if (!e.target.closest(".search-field")) {
    listaSugerencias.innerHTML = "";
    listaSugerencias.style.display = "none";
  }
});


    let esEscaneo = false;
    let modoFoto  = false;

    function limpiarCampos() {
        fMat.value = '';
        fNombre.value = '';
        fGrupo.value = '';
        fCarrera.value = '';
        fHora.value = '';
        fEstado.value = '';
        fotoAlumno.src = '';
        fotoAlumno.style.display = "none";
        btnAcceso.classList.add("hidden");
    }

    function mostrarFoto(url){
        fotoAlumno.src = url;
        fotoAlumno.style.display = "block";
    }

    function cerrarModalAlumno() {
        document.getElementById("modalAlumno").classList.remove("open");
        document.body.classList.remove("blur-active");
        modoFoto = false;
    }

    document.getElementById("btnCerrarModal")
        .addEventListener("click", cerrarModalAlumno);

btnAcceso.addEventListener('click', function () {
    if (modoFoto) return;

    registrarAcceso();

    // 🔹 LIMPIAR CAMPOS DEL ALUMNO
    limpiarCampos();

    // 🔹 LIMPIAR BUSCADOR DE MATRÍCULA
    input.value = '';

    // 🔹 DEVOLVER FOCO AL BUSCADOR
    input.focus();
});


    function registrarAcceso() {
        if (modoFoto) return;

        const matricula = fMat.value;
        const nombre    = fNombre.value;
        const grupo     = fGrupo.value;
        const hora      = fHora.value;

        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${hora}</td>
            <td>${matricula}</td>
            <td>${nombre}</td>
            <td>${grupo}</td>
            <td>Acceso Permitido</td>
        `;
        document.getElementById("historialBody").prepend(fila);

        const ahora = new Date();
        const fecha = ahora.getFullYear() + "-" +
                      String(ahora.getMonth() + 1).padStart(2, "0") + "-" +
                      String(ahora.getDate()).padStart(2, "0");

fetch('control_acceso_insertar.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `matricula=${encodeURIComponent(matricula)}&nombre=${encodeURIComponent(nombre)}&grupo=${encodeURIComponent(grupo)}&hora=${hora}&fecha=${fecha}&resultado=Acceso Permitido`
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        cargarHistorial(); // ✅ recarga la tabla con el nuevo registro
    } else {
        alert("Error: " + (data.error || "No se pudo registrar asistencia"));
    }
});

    }

    function obtenerAlumno(matricula, mostrarModal = false){
        if(!matricula) return;

        fetch('buscar_alumno.php?matricula=' + encodeURIComponent(matricula))
        .then(res => res.json())
        .then(data => {

            if (data.error) {
                limpiarCampos();
                alert('⚠️ ' + data.error);
                return;
            }

            // ===== MODO NORMAL =====
            if (!modoFoto) {
                fMat.value     = data.matricula;
                fNombre.value  = data.nombre;
                fGrupo.value   = data.carrera;
                fCarrera.value = data.grupo;

                const ahora = new Date();
                fHora.value   = ahora.toLocaleTimeString('es-MX', { hour12: false });
                fEstado.value = esEscaneo ? "Permitido" : '';

                mostrarFoto(data.foto);
                btnAcceso.classList.remove("hidden");
            }

            // ===== MODO FOTOGRAFÍA (SOLO MODAL) =====
            if (modoFoto && mostrarModal) {

                document.getElementById("modalMat").value     = data.matricula;
                document.getElementById("modalNombre").value  = data.nombre;
                document.getElementById("modalGrupo").value   = data.carrera;
                document.getElementById("modalCarrera").value = data.grupo;
                document.getElementById("modalFoto").src      = data.foto || '';

                document.getElementById("modalAlumno")
                    .classList.add("open");
                document.body.classList.add("blur-active");
            }

            if (esEscaneo && !modoFoto) {
                setTimeout(() => {
                    registrarAcceso();
                    limpiarCampos();
                    esEscaneo = false;
                }, 5000);
            }
        });
    }

    /* ===============================
       ESCÁNER FÍSICO
       =============================== */
    let bufferEscaner = '';
    let timerEscaner = null;

    document.addEventListener('keydown', function (e) {
        if (modoFoto) return;
        if (e.key.length !== 1) return;

        bufferEscaner += e.key;
        clearTimeout(timerEscaner);

        timerEscaner = setTimeout(() => {
            if (bufferEscaner.length >= 5) {
                esEscaneo = true;
                const mat = bufferEscaner.replace(/[^a-zA-Z0-9-]/g, '').trim();
                input.value = mat;
                obtenerAlumno(mat, false);
            }
            bufferEscaner = '';
        }, 80);
    });

    /* ===============================
       BÚSQUEDA MANUAL
       =============================== */
    form.addEventListener('submit', function(e){
        if (modoFoto) return;
        e.preventDefault();
        const mat = input.value.trim();
        if (!mat) return limpiarCampos();
        esEscaneo = false;
        obtenerAlumno(mat, false);
    });

    /* ===============================
       ACTIVAR MODO FOTOGRAFÍA
       =============================== */
    const scanValidoPHP    = <?php echo json_encode($scanValido); ?>;
    const scanMatriculaPHP = <?php echo json_encode($scanMatricula); ?>;

    if (scanValidoPHP && scanMatriculaPHP) {
        modoFoto  = true;
        esEscaneo = false;
        obtenerAlumno(scanMatriculaPHP, true);
    }

})();
</script>
    function cargarHistorial() {
  fetch("historial.php")
    .then(res => res.text())
    .then(html => {
      document.getElementById("historialBody").innerHTML = html;
    });
}

// Cargar historial al inicio
cargarHistorial();

</body>
</html>
