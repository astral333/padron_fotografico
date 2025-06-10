<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('padron.xlsx');
$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();
$header = array_shift($data);

$ix_dni = array_search("DNI", $header);
$ix_codigo = array_search("CODIGO", $header);
$ix_ap1 = array_search("AP. PATERNO", $header);
$ix_ap2 = array_search("AP. MATERNO", $header);
$ix_nom = array_search("NOMBRE", $header);

$html = '<style>
body { font-family: sans-serif; font-size: 11px; }
table { width: 100%; border-collapse: collapse; }
td {
  width: 25%; height: 250px; border: 1px solid #000;
  text-align: center; vertical-align: top; padding: 5px;
}
img {
  width: 90px; height: 110px; object-fit: cover; margin: 6px 0;
}
.firma {
  font-weight: bold; margin-top: 10px;
}
.nombre {
  font-weight: bold;
}
</style>';

$html .= '<table><tr>';
$contador = 1;
$columna = 0;

foreach ($data as $row) {
    $dni     = $row[$ix_dni];
    $codigo  = $row[$ix_codigo];
    $apellidoNombre = strtoupper("{$row[$ix_ap1]} {$row[$ix_ap2]}<br>{$row[$ix_nom]}");

    $fotoPath = "fotos/$dni.jpg";
    $fotoBase64 = file_exists($fotoPath) ? base64_encode(file_get_contents($fotoPath)) : '';

    $html .= '<td>';
    $html .= "<div><strong>$contador</strong><br>$codigo</div>";
    $html .= $fotoBase64
        ? "<img src='data:image/jpeg;base64,$fotoBase64'>"
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

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('padron_fotografico.pdf', ['Attachment' => 0]);
?>
