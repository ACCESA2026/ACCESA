<?php
// Control_Acceso/modo_fotografia.php
session_start();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modo fotografía · Escáner</title>
  <style>
    body{font-family:system-ui,Segoe UI,Arial;margin:0;background:#0b4d3d;color:#fff}
    header{padding:14px 16px;background:#083c30;display:flex;gap:10px;align-items:center;justify-content:space-between}
    a{color:#fff;text-decoration:none;background:rgba(255,255,255,.12);padding:8px 12px;border-radius:10px}
    main{padding:14px 16px;max-width:900px;margin:0 auto}
    .card{background:#fff;color:#111;border-radius:16px;padding:14px}
    video{width:100%;border-radius:12px;background:#000}
    .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
    button{border:0;border-radius:12px;padding:10px 14px;font-weight:600;cursor:pointer}
    .ok{background:#0a7a3d;color:#fff}
    .stop{background:#b3261e;color:#fff}
    .muted{opacity:.85;font-size:.9rem}
    #msg{margin-top:10px;font-weight:600}
  </style>
</head>
<body>
<header>
  <div>📷 Modo fotografía</div>
  <a href="control_acceso.php">← Volver</a>
</header>

<main>
  <div class="card">
    <div class="muted">Apunta al código de barras del alumno. En cuanto lo detecte, te regresará a Control de Acceso y llenará los datos.</div>
    <video id="video" playsinline></video>

    <div class="row">
      <button class="ok" id="btnStart" type="button">Iniciar cámara</button>
      <button class="stop" id="btnStop" type="button">Detener</button>
    </div>

    <div id="msg"></div>
  </div>
</main>

<!-- ZXing (lector de códigos en navegador) -->
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.21.2/umd/index.min.js"></script>
<script>
  const video = document.getElementById('video');
  const msg = document.getElementById('msg');
  const btnStart = document.getElementById('btnStart');
  const btnStop = document.getElementById('btnStop');

  let codeReader = null;
  let running = false;

  function setMsg(t, ok=true){
    msg.textContent = t;
    msg.style.color = ok ? '#0a7a3d' : '#b3261e';
  }

  function limpiarCodigo(txt){
    txt = (txt || '').toString().trim();
    txt = txt.replace(/[^\x20-\x7E]/g,'');       // quita invisibles
    txt = txt.replace(/[^0-9A-Za-z\-]/g,'');     // deja letras/números/guion
    return txt;
  }

  async function start(){
    try{
      setMsg('Iniciando cámara...', true);

      codeReader = new ZXing.BrowserMultiFormatReader();
      running = true;

      // Fuerza cámara trasera si existe
      const constraints = { video: { facingMode: { ideal: "environment" } }, audio: false };

      await codeReader.decodeFromConstraints(constraints, video, async (result, err) => {
        if (!running) return;

        if (result) {
          const raw = result.getText();
          const code = limpiarCodigo(raw);

          if (!code) {
            setMsg('Leí algo pero quedó vacío. Intenta de nuevo.', false);
            return;
          }

          // (Opcional) Validar con tu backend antes de regresar:
          const fd = new FormData();
          fd.append('matricula', code);

          const r = await fetch('consulta_barra.php', { method:'POST', body: fd });
          const j = await r.json();

          if (j && j.valido) {
            const mat = (j.data && j.data.Matricula) ? j.data.Matricula : code;
            setMsg('✅ Detectado: ' + mat + ' ... cargando', true);

            // Regresa a Control de Acceso y dispara auto-carga
            window.location.href = 'control_acceso.php?scan=1&matricula=' + encodeURIComponent(mat);
          } else {
            setMsg('⚠️ No se encontró en BD: ' + code, false);
          }
        }
      });

    }catch(e){
      setMsg('❌ No pude abrir la cámara. Asegúrate de usar HTTPS y dar permiso.', false);
      console.error(e);
    }
  }

  function stop(){
    running = false;
    try{ if (codeReader) codeReader.reset(); }catch(e){}
    setMsg('Cámara detenida.', true);
  }

  btnStart.addEventListener('click', start);
  btnStop.addEventListener('click', stop);

  // Auto-inicia (para que en teléfono sea directo)
  start();
</script>
</body>
</html>
