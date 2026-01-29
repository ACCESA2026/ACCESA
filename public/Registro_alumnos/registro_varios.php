<?php
// ======================== CONFIGURACIÓN =========================
$encabezadosEsperados = [
  "Matrícula",
  "Primer apellido",
  "Segundo apellido",
  "Nombre",
  "Grupo Referente",
  "Sexo",
  "CURP",
  "Fecha Nacimiento",
  "Email Institucional",
  "Plan Estudios",
  "Plan Estudios Cve",
  "Tutor Nombre",
  "Dom Colonia",
  "Calle",
  "Edad",
  "Médico Institución Médica",
  "Dom CP",
  "Dom Ciudad",
  "Teléfono",
  "Tel Celular",
  "E-mail",
  "Municipio",
  "Tutor celular"
];

$alert = null;

// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
  $archivo = $_FILES['archivo_excel'];
  $permitidos = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel'
  ];

  if (!in_array($archivo['type'], $permitidos)) {
    $alert = [
      'icon' => 'error',
      'title' => 'Archivo no válido',
      'html'  => 'Solo se permiten archivos <b>.xlsx</b> o <b>.xls</b>.'
    ];
  } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
    $alert = [
      'icon' => 'error',
      'title' => 'Error al subir',
      'html'  => 'Código de error: ' . $archivo['error']
    ];
  } else {
    // ===== Validar encabezados =====
    $zip = new ZipArchive;
    if ($zip->open($archivo['tmp_name']) === TRUE) {
      $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
      $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
      $zip->close();

      if ($xml && $sharedStringsXML) {
        $shared = [];
        $sxml = simplexml_load_string($sharedStringsXML);
        foreach ($sxml->si as $si) $shared[] = (string)$si->t;

        $sxmlSheet = simplexml_load_string($xml);
        $row = $sxmlSheet->sheetData->row[0];
        $encabezadosLeidos = [];
        foreach ($row->c as $cell) {
          $valor = (string)$cell->v;
          $tipo = (string)$cell['t'];
          $encabezadosLeidos[] = $tipo === 's' ? $shared[$valor] : $valor;
        }

        $faltantes = array_diff($encabezadosEsperados, $encabezadosLeidos);
        $sobrantes = array_diff($encabezadosLeidos, $encabezadosEsperados);

        if (empty($faltantes) && empty($sobrantes)) {
          // Guardar temporalmente el archivo subido
          $destinoDir = __DIR__ . "/Base de Datos Subidos/";
          if (!file_exists($destinoDir)) mkdir($destinoDir, 0777, true);
          $nombreArchivo = "excel_tmp_" . date("Ymd_His") . ".xlsx";
          $rutaArchivo = $destinoDir . $nombreArchivo;
          move_uploaded_file($archivo['tmp_name'], $rutaArchivo);

          // Si todo está bien, alert de éxito
          $alert = [
            'icon' => 'success',
            'title' => '✅ Archivo válido',
            'html'  => "Los encabezados son correctos.<br>Se procederá a importar los datos automáticamente.",
            'file'  => $nombreArchivo
          ];
        } else {
          $html = "";
          if (!empty($faltantes))
            $html .= "<b>Faltan columnas:</b><br><span style='color:red'>• " . implode("<br>• ", $faltantes) . "</span><br><br>";
          if (!empty($sobrantes))
            $html .= "<b>Columnas no reconocidas:</b><br><span style='color:orange'>• " . implode("<br>• ", $sobrantes) . "</span>";

          $alert = [
            'icon' => 'error',
            'title' => '❌ Los encabezados no coinciden',
            'html'  => $html
          ];
        }
      } else {
        $alert = [
          'icon' => 'error',
          'title' => 'Error en el archivo',
          'html'  => 'No se pudo leer la estructura interna del archivo Excel.'
        ];
      }
    } else {
      $alert = [
        'icon' => 'error',
        'title' => 'Error',
        'html'  => 'No se pudo abrir el archivo XLSX.'
      ];
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Subir Excel · CONALEP</title>

  <link rel="stylesheet" href="excel_cargados.css" />
  <link rel="stylesheet" href="../Centro_logo/header.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

  <?php include '../Centro_logo/header.php'; ?>

  <main class="upload-wrapper">
    <div class="upload-card">
      <div class="upload-header">
        <h1>📄 Subir archivo Excel</h1>
        <a href="index.php" class="btn-back">← Atrás</a>
      </div>

      <form method="POST" enctype="multipart/form-data" id="formExcel" class="upload-form">
        <label for="archivo_excel" class="file-drop">
          <input type="file" name="archivo_excel" id="archivo_excel" accept=".xlsx,.xls" required>
          <div class="drop-zone">
            <span id="file-name">Arrastra tu archivo aquí o haz clic para seleccionarlo</span>
          </div>
        </label>
        <button type="submit" class="btn-upload">Subir archivo</button>
      </form>
    </div>
  </main>

  <script>
    document.getElementById("archivo_excel").addEventListener("change", function(){
      const fileName = document.getElementById("file-name");
      if (this.files.length > 0) {
        fileName.textContent = this.files[0].name;
        fileName.style.color = "#0B4D3D";
        fileName.style.fontWeight = "600";
      }
    });

    <?php if (isset($alert)): ?>
      Swal.fire({
        icon: '<?= $alert['icon'] ?>',
        title: '<?= $alert['title'] ?>',
        html: `<?= $alert['html'] ?>`,
        confirmButtonColor: '<?= $alert['icon'] === 'success' ? '#0B4D3D' : '#d33' ?>',
        width: '600px'
      }).then(() => {
        <?php if (isset($alert['file'])): ?>
          // Llamar automáticamente a importar_excel.php
          const formData = new FormData();
          formData.append('ruta_archivo', '<?= $alert['file'] ?>');
          fetch('importar_excel.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(html => {
            const blob = new Blob([html], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            window.location.href = 'importar_excel.php?ok=1';
            document.open();
            document.write(html);
            document.close();
          });
        <?php endif; ?>
      });
    <?php endif; ?>
  </script>

</body>
</html>
