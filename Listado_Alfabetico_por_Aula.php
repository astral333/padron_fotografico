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
$numerodeAula= isset($_GET['aula']) ? $_GET['aula'] : 1;
// Filtrar por Aula 1
$data_aula1 = array_filter($data, fn($row) => trim($row[$ix_aula]) == $numerodeAula);

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
    width: 25%; height: 90px; border: 1px solid #000;
    text-align: center; vertical-align: top; padding: 5px;
}
img.foto {
    width: 90px; height: 110px; object-fit: cover; margin: 6px 0;
}
.nombre { font-weight: bold; }
.firma { font-weight: bold; margin-top: 10px;  height: 60px;}
</style>';


$contador = 1;
$columna = 0;
// Encabezado
$html .= '
<table style="width:100%; height:140px; margin-bottom:10px; border:1px solid transparent;">
    <tr>
        <td style="width:120px; text-align:left; vertical-align:middle; border:1px solid transparent;">
            <img src="data:image/png;base64,' . $logoBase64 . '" style="height:140px; width:140px; object-fit:cover;">
        </td>
        <td style=" vertical-align:middle; border:1px solid transparent;">
            <div style="font-size:28px; font-weight:bold; line-height:1.1; margin-bottom:8px;">
                UNIVERSIDAD NACIONAL<br>AUTONOMA DE CHOTA
            </div>
            <div style="font-size:18px; font-weight:bold; margin-bottom:8px;">
                PRIMER EXAMEN CEPRE 2025 - II
            </div>
            <div style="font-size:22px; font-weight:bold; margin-top:8px;">
                LISTADO ALFABÉTICO POR AULA
            </div>
        </td>
        <td style="width:30px; text-align:right; vertical-align:middle; border:1px solid transparent;">
            <div style="font-size:14px; margin-bottom:4px;">Aula</div>
            <div style="font-size:36px; font-weight:bold;">'.$numerodeAula.'</div>
        </td>
    </tr>
</table>';

// Fichas
$html .= '<table><tr>';
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
    $html .= "<div class='nombre'>DNI:$dni</div>";
    $html .= '</td>';

    $columna++;
    $contador++;

    if ($columna == 4) {
        $html .= '</tr>';
        if (($contador - 1) % 16 == 0) {
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
