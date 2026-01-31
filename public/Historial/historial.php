  <?php
  session_start();
  if (empty($_SESSION['usuario'])) {
    header('Location: ../Login/login.php');
    exit;
  }

  	require_once '../Login/conexion.php';




  /* ================================
    CONSULTA PRINCIPAL DEL HISTORIAL
  ================================== */

$sql = "
  SELECT 
      ca.Fecha,
      ca.Hora,
      ca.Matricula,
      cn.DsNombre AS Nombre,
      gs.DsGrupo AS Grupo,
      sm.DsSemestre AS Semestre,
      'Asistencia' AS Tipo,
      0 AS Faltas
  FROM casistencia ca
  JOIN cnombre cn ON ca.CvNombre = cn.CvNombre
  JOIN msemestre sm ON ca.CvSemestre = sm.CvSemestre
  JOIN mcredencial mc ON ca.Matricula = mc.Matricula
  JOIN mgrupo gs ON mc.CvGrupo = gs.CvGrupo
  ORDER BY ca.Fecha DESC, ca.Hora DESC
";




  $stmt = $conn->query($sql);
  $historial = $stmt->fetchAll();

  /* ================================
    CONSULTAS PARA LOS SELECT DINÁMICOS
  ================================== */

  $grupos = $conn->query("SELECT DsGrupo FROM mgrupo ORDER BY DsGrupo")->fetchAll();
  $planes = $conn->query("SELECT DsPlan FROM mplanestudios ORDER BY DsPlan")->fetchAll();
  ?>

  <!DOCTYPE html>
  <html lang="es">
  <head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Historial de estudiantes · CONALEP</title>

  <link rel="stylesheet" href="../Centro_logo/header.css" />
  <link rel="stylesheet" href="../Menu/menu.css" />
  <link rel="stylesheet" href="historial.css" />

  <style>
  /* =============================
    ESTILOS DE BUSCADOR
  ============================= */
  .search-bar {
    margin-top: 15px;
    display: flex;
    justify-content: flex-end;
    width: 100%;
  }

  .search-box {
    position: relative;
    display: flex;
    align-items: center;
    background: #fff;
    border-radius: 10px;
    border: 1px solid #ccc;
    padding: 4px 10px 4px 14px;
    width: 300px;
    transition: all 0.3s ease;
  }

  .search-box:hover,
  .search-box:focus-within {
    border-color: #0B4D3D;
    box-shadow: 0 0 6px rgba(11,77,61,0.3);
  }

  .search-box input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 14px;
  }

  /* =============================
    MODAL FILTRO (SOBRE LA INTERFAZ)
  ============================= */
  .modal-filtro {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.55);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }

  .modal-content {
    background: #fff;
    padding: 20px;
    width: 350px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    animation: fadeIn 0.2s ease-out;
  }

  .modal-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 12px;
  }

  .modal-content select,
  .modal-content input {
    margin-bottom: 10px;
    width: 100%;
    padding: 6px;
    border-radius: 6px;
    border: 1px solid #ccc;
  }

  .modal-actions {
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* =============================
    RESULTADOS
  ============================= */
  .resultado.ok { color: #0b7a34; font-weight: 600; }
  .resultado.no { color: #c62828; font-weight: 600; }

  .contador {
    margin-top: 10px;
    font-size: 0.9rem;
    text-align: right;
  }
  </style>
  </head>

  <body>

  <?php include '../Centro_logo/header.php'; ?>

  <main class="container" role="main">
  <div class="title-pill" style="margin-top:12px;">Historial de estudiantes</div>
<?php if (($_SESSION['usuario']['rol'] ?? '') === 'Administrador'): ?>
  <button class="btn-config" onclick="abrirConfigAsistencia()">
    ⚙️ Configuración de asistencia
  </button>
<?php endif; ?>
<!-- 🔍 BUSCADOR -->
<div class="search-bar">
  <div class="search-box">
    <input type="text" id="buscar" placeholder="Buscar por nombre o matrícula..." onkeyup="filtrarTabla()">
  </div>
</div>
  <!-- ============================
      TABLA PRINCIPAL
  ============================= -->
  <section class="card" style="margin-top:12px;">
  <header class="card-title">Accesos registrados</header>

  <div class="table-wrap">
  <table class="table" id="tablaHistorial">
  <thead>
  <tr>
    <th>Fecha</th>
    <th>Hora</th>
    <th>Matrícula</th>
    <th>Nombre</th>
    <th>Grupo</th>
    <th>Semestre</th>
    <th>Resultado</th>
    <th>Faltas</th> <!-- NUEVA COLUMNA -->
  </tr>
  </thead>


  <tbody>
  <?php foreach ($historial as $registro): ?>
  <tr>
    <td><?= htmlspecialchars($registro['Fecha']) ?></td>        <!-- FECHA -->
    <td><?= htmlspecialchars($registro['Hora']) ?></td>         <!-- HORA -->
    <td><?= htmlspecialchars($registro['Matricula']) ?></td>    <!-- MATRÍCULA -->
    <td><?= htmlspecialchars($registro['Nombre']) ?></td>       <!-- NOMBRE -->
    <td><?= htmlspecialchars($registro['Grupo']) ?></td>        <!-- GRUPO -->
    <td><?= htmlspecialchars($registro['Semestre']) ?></td>     <!-- SEMESTRE -->
    <td class="<?= $registro['Tipo'] === 'Asistencia' ? 'resultado ok' : 'resultado no' ?>">
    <?= $registro['Tipo'] ?>
</td>

<td><?= $registro['Faltas'] ?></td>
                                                  <!-- FALTAS -->
  </tr>
  <?php endforeach; ?>
  </tbody>

  </table>
  </div>

  <p class="contador" id="contador"></p>

  <!-- ============================
      MODAL DE FILTRO
  ============================= -->
  <div id="panelFiltro" class="modal-filtro">
  <div class="modal-content">

  <h3 class="modal-title">Filtrar historial</h3>

  <label>Fecha inicio:</label>
  <input type="date" id="fechaInicio" oninput="validarFechas()">
  <span id="errInicio" class="msg-error" style="display:none;"></span>

  <label>Fecha fin:</label>
  <input type="date" id="fechaFin" oninput="validarFechas()">
  <span id="errFin" class="msg-error" style="display:none;"></span>

  <label>Grupo:</label>
  <select id="grupo">
    <option value="">Todos</option>
    <?php foreach ($grupos as $g): ?>
      <option value="<?= $g['DsGrupo'] ?>"><?= $g['DsGrupo'] ?></option>
    <?php endforeach; ?>
  </select>

  <label>Plan de estudios:</label>
  <select id="plan">
    <option value="">Todos</option>
    <?php foreach ($planes as $p): ?>
      <option value="<?= $p['DsPlan'] ?>"><?= $p['DsPlan'] ?></option>
    <?php endforeach; ?>
  </select>

  <label>Faltas:</label>
  <select id="faltas">
    <option value="">Todos</option>
    <option value="1">1 falta</option>
    <option value="2">2 faltas</option>
    <option value="3">3 faltas o más</option>
  </select>

  <div class="modal-actions">
    <button class="btn btn-dark" onclick="aplicarFiltro()">Aplicar filtro</button>
    <button class="btn btn-danger" onclick="toggleFiltro()">Cerrar</button>
  </div>

  </div>
  </div>

  <!-- ============================
      BOTONES
  ============================= -->
  <div class="actions">
    <button class="btn btn-dark" onclick="exportarExcel()">Exportar</button>
    <button class="btn btn-dark" onclick="toggleFiltro()">Filtrar</button>
    <a href="../Menu/menu.php" class="btn btn-danger">Salir</a>
  </div>

  <footer class="footer">
    <small>© All Right Reserved. Designed by CONALEP Comitán Clave 070</small>
  </footer>
  </section>
  </main>


  <script src="../Centro_logo/header.js"></script>

  <script>
  /* ============================
    ABRIR / CERRAR MODAL
  ============================= */
  function toggleFiltro() {
    const panel = document.getElementById("panelFiltro");
    panel.style.display = (panel.style.display === "flex") ? "none" : "flex";
  }

  /* ============================
    BUSCADOR
  ============================= */
  function filtrarTabla() {
    const filtro = document.getElementById("buscar").value.toLowerCase();
    const filas = document.querySelectorAll("#tablaHistorial tbody tr");
    let count = 0;

    filas.forEach(fila => {
      const texto = fila.innerText.toLowerCase();
      const visible = texto.includes(filtro);
      fila.style.display = visible ? "" : "none";
      if (visible) count++;
    });

    actualizarContador(count);
  }

  /* ============================
    CONTADOR
  ============================= */
  function actualizarContador(mostrados = null) {
    const filas = document.querySelectorAll("#tablaHistorial tbody tr");
    const total = filas.length;

    if (mostrados === null) {
      mostrados = Array.from(filas).filter(f => f.style.display !== "none").length;
    }

    document.getElementById("contador").textContent =
      `Mostrando ${mostrados} de ${total} registros`;
  }

  /* ============================
    FILTRO AVANZADO
  ============================= */
async function aplicarFiltro() {

    const fi = document.getElementById("fechaInicio").value;
    const ff = document.getElementById("fechaFin").value;
    const grupo = document.getElementById("grupo").value;
    const plan = document.getElementById("plan").value;
    const faltasFiltro = document.getElementById("faltas").value;

    // 1️⃣ Ocultar TODAS las filas actuales (porque son asistencias)
    const tbody = document.querySelector("#tablaHistorial tbody");
    tbody.innerHTML = "";  // Limpiar tabla

    // 2️⃣ Cargar faltas reales desde la BD
    const url = `obtener_faltas_lista.php?fi=${fi}&ff=${ff}&grupo=${grupo}&plan=${plan}`;
    const resp = await fetch(url);
    const datos = await resp.json();

    // 3️⃣ Insertar solo las filas que cumplan el filtro de faltas
    datos.forEach(reg => {

        const faltas = parseInt(reg.Faltas);

        // FILTRO DE FALTAS
        if (faltasFiltro === "1" && faltas !== 1) return;
        if (faltasFiltro === "2" && faltas !== 2) return;
        if (faltasFiltro === "3" && faltas < 3) return;

        // Construcción de fila HTML
        const fila = document.createElement("tr");

        fila.innerHTML = `
            <td>${reg.Fecha}</td>
            <td>-</td>
            <td>${reg.Matricula}</td>
            <td>${reg.Nombre}</td>
            <td>${reg.Grupo}</td>
            <td>${reg.Semestre}</td>
            <td class="resultado no">Falta</td>
            <td>${faltas}</td>
        `;

        tbody.appendChild(fila);
    });

    actualizarContador();
    toggleFiltro();
}


  /* ============================
    EXPORTAR A EXCEL
  ============================= */
  function exportarExcel() {
    const tabla = document.getElementById("tablaHistorial").outerHTML;
    const dataType = 'application/vnd.ms-excel';
    const nombreArchivo = 'Historial_Estudiantes_' + new Date().toLocaleDateString() + '.xls';
    const enlace = document.createElement('a');
    enlace.href = 'data:' + dataType + ', ' + encodeURIComponent(tabla);
    enlace.download = nombreArchivo;
    enlace.click();
  }
  function validarFechas() {

    const fi = document.getElementById("fechaInicio");
    const ff = document.getElementById("fechaFin");
    const errInicio = document.getElementById("errInicio");
    const errFin = document.getElementById("errFin");
    const btnAplicar = document.querySelector(".modal-actions .btn.btn-dark");

    let valido = true;

    const hoy = "<?= date('Y-m-d'); ?>";

    // Resetear estilos
    fi.classList.remove("input-error");
    ff.classList.remove("input-error");
    errInicio.style.display = "none";
    errFin.style.display = "none";


    // VALIDACIÓN 1: fecha fin no puede ser mayor que hoy
    if (ff.value && ff.value > hoy) {
      ff.classList.add("input-error");
      errFin.textContent = "La fecha de fin no puede ser mayor que hoy.";
      errFin.style.display = "block";
      valido = false;
    }

    // VALIDACIÓN 2: fecha inicio no puede ser mayor que fecha fin
    if (fi.value && ff.value && fi.value > ff.value) {
      fi.classList.add("input-error");
      errInicio.textContent = "La fecha de inicio no puede ser mayor que la fecha de fin.";
      errInicio.style.display = "block";
      valido = false;
    }

    // Habilitar o deshabilitar botón
    btnAplicar.disabled = !valido;
    btnAplicar.style.opacity = valido ? "1" : "0.5";
  }
  document.addEventListener("DOMContentLoaded", function () {

    const fechaServidor = "<?= date('Y-m-d'); ?>";

    // Convertir la fecha del servidor a objeto
    const fechaHoy = new Date(fechaServidor);

    // Restar 15 días
    const fechaInicio = new Date(fechaHoy);
    fechaInicio.setDate(fechaInicio.getDate() - 15);

    // Formato YYYY-MM-DD
    const formatoInicio = fechaInicio.toISOString().split('T')[0];
    const formatoFin = fechaServidor;

    // Asignar valores a los inputs
    document.getElementById("fechaInicio").value = formatoInicio;
    document.getElementById("fechaFin").value = formatoFin;

    // Ejecutar validaciones instantáneas al cargar
    validarFechas();
  });
  function obtenerDiasHabiles(inicio, fin) {
      const dias = [];
      let fechaActual = new Date(inicio);

      const fechaFinal = new Date(fin);

      while (fechaActual <= fechaFinal) {
          const dia = fechaActual.getDay(); 
          // 1-5 = Lunes-Viernes
          if (dia >= 1 && dia <= 5) {
              dias.push(fechaActual.toISOString().split("T")[0]);
          }
          fechaActual.setDate(fechaActual.getDate() + 1);
      }

      return dias;
  }
      
  </script>
      <script>
function abrirConfigAsistencia(){
  document.getElementById("modalConfigAsistencia").style.display = "flex";
}
function cerrarConfigAsistencia(){
  document.getElementById("modalConfigAsistencia").style.display = "none";
}
</script>

<!-- ================= MODAL CONFIGURACIÓN ASISTENCIA ================= -->
<div id="modalConfigAsistencia" class="modal-config">

  <div class="modal-card">
    <div class="modal-header">
      <h3>Configuración de asistencia</h3>
      <button class="modal-close" onclick="cerrarConfigAsistencia()">×</button>
    </div>
  <form id="formHorario" class="modal-body" onsubmit="return false;">
        
      <label>Hora de apertura</label>
      <input type="time" name="apertura" required>
        
      <label>Hora de cierre</label>
      <input type="time" name="cierre" required>
        
      <div class="modal-actions">
        <button type="button" class="btn btn-dark" onclick="guardarHorario()">Guardar</button>
        <button type="button" class="btn btn-light" onclick="cerrarConfigAsistencia()">Cancelar</button>
      </div>
    </form>
  </div>

</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

  const form = document.getElementById("formHorario");
  if (!form) return;

  form.addEventListener("submit", function(e){
    e.preventDefault();
  });

});
</script>

<script>
async function guardarHorario() {

  const form = document.getElementById("formHorario");
  const data = new FormData(form);

  try {
    const resp = await fetch("guardar_horario.php", {
      method: "POST",
      body: data
    });

    const res = await resp.json();

if (res.ok) {
  alert(res.msg || "Horario guardado correctamente");
  cerrarConfigAsistencia();
  form.reset();
} else {
  alert(res.msg || "Error al guardar");
}


  } catch (e) {
    mostrarToast("Error de conexión", true);
  }
}
</script>

  </body>
  </html>
