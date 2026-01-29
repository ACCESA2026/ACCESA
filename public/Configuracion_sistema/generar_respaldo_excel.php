<?php
// ============================================================
//   RESPALDO CONALEP - ZIP (EXCEL: 2 HOJAS + SQL)
// ============================================================

session_start();
require_once __DIR__ . '/../Login/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

date_default_timezone_set('America/Mexico_City');

$fecha     = date('Y-m-d H:i:s');
$fechaFile = date('Y-m-d_His');
$nombreBase = "respaldo_conalep_{$fechaFile}";

// ============================================================
// ✅ FUNCIÓN: PINTAR HOJA BONITA (ENCABEZADOS, BORDES, FILTROS, ETC.)
// ============================================================
function pintarHojaBonita($sheet, array $datos, string $tituloHoja = 'Hoja', array $opts = [])
{
    $sheet->setTitle($tituloHoja);

    if (!$datos) {
        $sheet->setCellValue('A1', 'Sin registros');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        return;
    }

    $columnas = array_keys($datos[0]);
    $totalCols = count($columnas);
    $ultimaColLetra = Coordinate::stringFromColumnIndex($totalCols);

    // -------- ENCABEZADOS (fila 1) ----------
    $colIndex = 1;
    foreach ($columnas as $col) {
        $letra = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue($letra.'1', $col);
        $colIndex++;
    }

    // Estilo encabezado (verde institucional)
    $sheet->getStyle("A1:{$ultimaColLetra}1")->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '0B4D3D'],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
            'horizontal' => Alignment::HORIZONTAL_LEFT,
        ],
    ]);

    // -------- DATOS (desde fila 2) ----------
    $filaExcel = 2;
    foreach ($datos as $fila) {
        $colIndex = 1;
        foreach ($columnas as $colNombre) {
            $valor = $fila[$colNombre];

            $letra = Coordinate::stringFromColumnIndex($colIndex);

            // todo como texto para no perder ceros / formatos
            $sheet->setCellValueExplicit(
                $letra.$filaExcel,
                (string)$valor,
                DataType::TYPE_STRING
            );

            $colIndex++;
        }
        $filaExcel++;
    }

    $ultimaFila = $filaExcel - 1;

    // -------- BORDES a toda la tabla ----------
    $sheet->getStyle("A1:{$ultimaColLetra}{$ultimaFila}")->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D9D9D9']
            ],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);

    // -------- FILTROS + CONGELAR ----------
    $sheet->setAutoFilter("A1:{$ultimaColLetra}1");
    $sheet->freezePane('A2');

    // -------- AUTOAJUSTE (con excepciones) ----------
    // Por defecto autosize, pero si hay columnas largas se les pone ancho fijo.
    $wrapCols = $opts['wrapCols'] ?? []; // ej: ['Descripcion']
    $fixedWidths = $opts['fixedWidths'] ?? []; // ej: ['Descripcion' => 55]

    for ($i = 1; $i <= $totalCols; $i++) {
        $colName = $columnas[$i-1];
        $colLetra = Coordinate::stringFromColumnIndex($i);

        if (isset($fixedWidths[$colName])) {
            $sheet->getColumnDimension($colLetra)->setWidth((float)$fixedWidths[$colName]);
        } else {
            $sheet->getColumnDimension($colLetra)->setAutoSize(true);
        }

        // wrap text en columnas indicadas
        if (in_array($colName, $wrapCols, true)) {
            $sheet->getStyle($colLetra."2:".$colLetra.$ultimaFila)->getAlignment()->setWrapText(true);
        }
    }

    // Ajuste visual de alto de encabezado
    $sheet->getRowDimension(1)->setRowHeight(22);

    // Si hay wrap, conviene aumentar un poco la altura de filas
    if (!empty($wrapCols)) {
        for ($r = 2; $r <= $ultimaFila; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(-1); // auto
        }
    }
}

// ============================================================
// 1️⃣ CONSULTA PRINCIPAL (HOJA 1: BASE DE DATOS)
// ============================================================
// ⚠️ AJUSTA SI TU TABLA BASE ES OTRA
$sqlExcel = "
    SELECT *
    FROM mCredencial
    ORDER BY Matricula
";
$stmt = $conn->query($sqlExcel);
$datosCredencial = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 2️⃣ CONSULTA REPORTES (HOJA 2: REPORTES ALUMNOS)
// ============================================================
// ✅ aquí jalamos lo más útil para visualizarlo bien.
// Si tu tabla tiene más/menos columnas, no pasa nada, porque pintamos dinámico.
$sqlReportes = "
    SELECT *
    FROM rReportesAlumnos
    ORDER BY FechaIncidente DESC
";
try {
    $stmt2 = $conn->query($sqlReportes);
    $datosReportes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $datosReportes = [];
}

// ============================================================
// 3️⃣ CREAR EXCEL (2 HOJAS)
// ============================================================
$spreadsheet = new Spreadsheet();

// Hoja 1 (activa)
$hoja1 = $spreadsheet->getActiveSheet();
pintarHojaBonita($hoja1, $datosCredencial, 'Base de datos', [
    // si detectas una columna larga en mCredencial, puedes fijarla aquí
    // 'fixedWidths' => ['TutorNombreCompleto' => 35],
]);

// Hoja 2 (reportes)
$hoja2 = $spreadsheet->createSheet();
pintarHojaBonita($hoja2, $datosReportes, 'Reportes alumnos', [
    // ✅ columnas típicas que conviene "wrap" y ancho fijo
    'wrapCols' => ['Descripcion'],
    'fixedWidths' => [
        'Descripcion' => 60,
        'Evidencia'   => 28,
        'NombreAlumno'=> 32,
        'TipoReporte' => 28,
    ],
]);

// ============================================================
// 4️⃣ GUARDAR EXCEL
// ============================================================
$excelPath = __DIR__ . "/{$nombreBase}.xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($excelPath);

// ============================================================
// 5️⃣ RESPALDO SQL (TODAS LAS TABLAS)
// ============================================================
$tablas = [
  'cNombre','cApellido','cGenero','cPuesto','cColonia','cCalle',
  'cTipPersona','mDireccion','mDatPerson','mUsuarios','mAplicacion',
  'mAcceso','mTurno','mSemestre','mCredencial','cAsistencia','rReportesAlumnos'
];

$sqlFile = __DIR__ . "/{$nombreBase}.sql";
$backup = "-- RESPALDO BD CONALEP - $fecha\n\n";
$backup .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tablas as $tabla) {

    $backup .= "\n-- ESTRUCTURA $tabla\n";
    try {
        $row = $conn->query("SHOW CREATE TABLE $tabla")->fetch(PDO::FETCH_ASSOC);
        $backup .= $row['Create Table'].";\n\n";
    } catch (Throwable $e) {
        $backup .= "-- ⚠ Tabla no encontrada: $tabla\n\n";
        continue;
    }

    $backup .= "-- DATOS $tabla\n";
    try {
        $res = $conn->query("SELECT * FROM $tabla");
        while ($fila = $res->fetch(PDO::FETCH_ASSOC)) {
            $cols = array_map(fn($v)=>"`$v`", array_keys($fila));
            $vals = array_map(fn($v)=>$conn->quote($v), array_values($fila));
            $backup .= "INSERT INTO `$tabla` (".implode(",", $cols).") VALUES (".implode(",", $vals).");\n";
        }
    } catch (Throwable $e) {
        $backup .= "-- ⚠ Error leyendo datos\n";
    }
}

file_put_contents($sqlFile, $backup);

// ============================================================
// 6️⃣ ZIP FINAL
// ============================================================
$zipPath = __DIR__ . "/{$nombreBase}.zip";
$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    $zip->addFile($excelPath, basename($excelPath));
    $zip->addFile($sqlFile, basename($sqlFile));
    $zip->close();
}

// ============================================================
// 7️⃣ DESCARGA
// ============================================================
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=".basename($zipPath));
header("Content-Length: ".filesize($zipPath));
readfile($zipPath);

unlink($excelPath);
unlink($sqlFile);
unlink($zipPath);
exit;
?>
