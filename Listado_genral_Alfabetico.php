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
$ix_grupo   = array_search("GRUPO", $header);
$ix_carrera = array_search("CARRERA", $header);

// Ordenar todo el padrón alfabéticamente por apellidos y nombres
usort($data, function($a, $b) use ($ix_ap1, $ix_ap2, $ix_nom) {
    return strcmp(
        strtoupper($a[$ix_ap1] . $a[$ix_ap2] . $a[$ix_nom]),
        strtoupper($b[$ix_ap1] . $b[$ix_ap2] . $b[$ix_nom])
    );
});

// Logo
$logoPath = 'img/logo.png';
$logoBase64 = base64_encode(file_get_contents($logoPath));

// HTML
$html = '<style>
body { font-family: sans-serif; font-size: 11px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #000; padding: 4px; }
th { background: #f5f5f5; font-size: 12px; }
img.foto { width: 60px; height: 70px; object-fit: cover; }
.titulo { font-size: 22px; font-weight: bold; text-align: center; margin-bottom: 10px; }
</style>';

// Encabezado
$html .= '
<table style="width:100%; margin-bottom:10px; border:1px solid transparent;">
    <tr>
        <td style="width:100px; text-align:left; vertical-align:middle;">
            <img src="data:image/png;base64,' . $logoBase64 . '" style="height:90px; width:90px; object-fit:cover;">
        </td>
        <td style="vertical-align:middle; text-align:center;">
            <div class="titulo">
                UNIVERSIDAD NACIONAL AUTÓNOMA DE CHOTA<br>
                PRIMER EXAMEN CEPRE 2025 - II<br>
                LISTADO GENERAL ALFABÉTICO DE POSTULANTES
            </div>
        </td>
    </tr>
</table>';

// Tabla de registros
$html .= '<table>
    <thead>
        <tr>
            <th>N°</th>
            <th>Foto</th>
            <th>Código</th>
            <th>DNI</th>
            <th>Apellidos y Nombres</th>
            <th>Grupo</th>
            <th>Aula</th>
            <th>Carrera</th>
        </tr>
    </thead>
    <tbody>
';

$contador = 1;
foreach ($data as $row) {
    $dni     = $row[$ix_dni];
    $codigo  = $row[$ix_codigo];
    $apellidosNombres = strtoupper($row[$ix_ap1] . ' ' . $row[$ix_ap2] . ' ' . $row[$ix_nom]);
    $grupo   = $ix_grupo !== false ? $row[$ix_grupo] : '';
    $aula    = $row[$ix_aula];
    $carrera = $ix_carrera !== false ? $row[$ix_carrera] : '';

    $fotoPath = "fotos/$dni.jpg";
    $fotoBase64 = file_exists($fotoPath) ? base64_encode(file_get_contents($fotoPath)) : '';

    $html .= '<tr>';
    $html .= '<td style="text-align:center;">' . $contador . '</td>';
    $html .= '<td style="text-align:center;">';
    $html .= $fotoBase64
        ? "<img class='foto' src='data:image/jpeg;base64,$fotoBase64'>"
        : "<div style='width:60px;height:70px;border:1px solid #ccc;margin:auto;'></div>";
    $html .= '</td>';
    $html .= '<td style="text-align:center;">' . $codigo . '</td>';
    $html .= '<td style="text-align:center;">' . $dni . '</td>';
    $html .= '<td>' . $apellidosNombres . '</td>';
    $html .= '<td style="text-align:center;">' . $grupo . '</td>';
    $html .= '<td style="text-align:center;">' . $aula . '</td>';
    $html .= '<td>' . $carrera . '</td>';
    $html .= '</tr>';

    $contador++;
}

$html .= '</tbody></table>';

// PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('listado_general_alfabetico.pdf', ['Attachment' => 0]);
?>
