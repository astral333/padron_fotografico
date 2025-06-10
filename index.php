<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Leer Excel
$spreadsheet = IOFactory::load('padron.xlsx');
$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();
$header = array_shift($data);

// Índices
$ix_dni     = array_search("DNI", $header);
$ix_codigo  = array_search("CODIGO", $header);
$ix_ap1     = array_search("AP. PATERNO", $header);
$ix_ap2     = array_search("AP. MATERNO", $header);
$ix_nom     = array_search("NOMBRE", $header);
$ix_aula    = array_search("AULA", $header);

// Filtrar por Aula 1
$data_aula1 = array_filter($data, fn($row) => trim($row[$ix_aula]) == '1');

// Ordenar alfabéticamente por apellidos + nombres
usort($data_aula1, function($a, $b) use ($ix_ap1, $ix_ap2, $ix_nom) {
    return strcmp(
        strtoupper($a[$ix_ap1] . $a[$ix_ap2] . $a[$ix_nom]),
        strtoupper($b[$ix_ap1] . $b[$ix_ap2] . $b[$ix_nom])
    );
});

// Inicia HTML
$logoPath = 'img/logo.png';
$logoBase64 = base64_encode(file_get_contents($logoPath));

$html = '<style>
body { font-family: sans-serif; font-size: 11px; }
.header {
    text-align: center;
    margin-bottom: 20px;
}
.header img {
    height: 80px;
}
.titulo {
    font-size: 14px;
    font-weight: bold;
}
.subtitulo {
    font-size: 12px;
}
table { width: 100%; border-collapse: collapse; }
td {
    width: 25%; height: 250px; border: 1px solid #000;
    text-align: center; vertical-align: top; padding: 5px;
}
img.foto {
    width: 90px; height: 110px; object-fit: cover; margin: 6px 0;
}
.nombre { font-weight: bold; }
.firma { font-weight: bold; margin-top: 10px; }
</style>';

// Encabezado
$html .= '<div class="header">
    <img src="data:image/png;base64,' . $logoBase64 . '"><br>
    <div class="titulo">UNIVERSIDAD NACIONAL AUTÓNOMA DE CHOTA</div>
    <div class="subtitulo">SEGUNDO EXAMEN CEPRE 2025 - 1</div>
    <div class="titulo">LISTADO ALFABÉTICO POR AULA</div>
    <div class="subtitulo" style="text-align:right; margin-top:-40px;">Aula<br><span style="font-size:18px;">1</span></div>
</div>';

// Fichas
$html .= '<table><tr>';
$contador = 1;
$columna = 0;

foreach ($data_aula1 as $row) {
    $dni     = $row[$ix_dni];
    $codigo  = $row[$ix_codigo];
    $apellidoNombre = strtoupper("{$row[$ix_ap1]} {$row[$ix_ap2]}<br>{$row[$ix_nom]}");

    $fotoPath = "fotos/$dni.jpg";
    $fotoBase64 = file_exists($fotoPath) ? base64_encode(file_get_contents($fotoPath)) : '';

    $html .= '<td>';
    $html .= "<div><strong>$contador</strong><br>$codigo</div>";
    $html .= $fotoBase64
        ? "<img class='foto' src='data:image/jpeg;base64,$fotoBase64'>"
        : "<div style='width:90px;height:110px;border:1px solid #ccc;margin:6px auto;'></div>";
    $html .= "<div class='nombre'>$apellidoNombre</div>";
    $html .= "<div class='firma'>Firma</div>";
    $html .= '</td>';

    $columna++;
    $contador++;

    if ($columna == 4) {
        $html .= '</tr>';
        if (($contador - 1) % 12 == 0) {
            $html .= '</table><div style="page-break-after:always;"></div><table><tr>';
        } else {
            $html .= '<tr>';
        }
        $columna = 0;
    }
}

if ($columna > 0 && $columna < 4) {
    for ($i = $columna; $i < 4; $i++) {
        $html .= '<td></td>';
    }
    $html .= '</tr>';
}
$html .= '</table>';

// PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('padron_aula_1.pdf', ['Attachment' => 0]);
?>
