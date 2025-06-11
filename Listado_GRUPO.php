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
$ix_grupo = array_search("GRUPO", $header); // Asegúrate que tu Excel tenga la columna GRUPO
$ix_local = array_search("LOCAL", $header); // Asegúrate que tu Excel tenga la columna LOCAL

// Filtrar por Aula 1
$numerodeAula= 8; // Cambia este valor según el aula que necesites
$data_aula1 = array_filter($data, fn($row) => trim($row[$ix_aula]) == $numerodeAula);

// Ordenar alfabéticamente por apellidos + nombres
usort($data_aula1, function($a, $b) use ($ix_ap1, $ix_ap2, $ix_nom) {
    return strcmp(
        strtoupper($a[$ix_ap1] . $a[$ix_ap2] . $a[$ix_nom]),
        strtoupper($b[$ix_ap1] . $b[$ix_ap2] . $b[$ix_nom])
    );
});

// Agrupar por grupo
$grupos = [];
foreach ($data as $row) {
    $grupo = isset($row[$ix_grupo]) ? trim($row[$ix_grupo]) : 'SIN GRUPO';
    $grupos[$grupo][] = $row;
}

// Ordenar grupos alfabéticamente
ksort($grupos);

$html = '<style>
body { font-family: sans-serif; font-size: 12px; }
.titulo { font-size: 28px; font-weight: bold; text-align: center; margin-bottom: 10px; }
.tabla { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
.tabla th, .tabla td { border-bottom: 1px solid #000; padding: 2px 6px; }
.tabla th { font-size: 13px; font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; }
.tabla td { font-size: 12px; }
.grupo-header { text-align: right; font-size: 13px; font-weight: bold; }
.grupo-letra { font-size: 36px; font-weight: bold; text-align: right; }
.sep { border-bottom: 2px dashed #000; }
</style>';


$grupo_filtrar = isset($_GET['grupo']) ? $_GET['grupo'] : 'A'; // Por defecto A
foreach ($grupos as $grupo => $registros) {
    if ($grupo !== $grupo_filtrar) continue; // Solo muestra el grupo seleccionado

    // Ordenar alfabéticamente por apellidos y nombres
    usort($registros, function($a, $b) use ($ix_ap1, $ix_ap2, $ix_nom) {
        return strcmp(
            strtoupper($a[$ix_ap1] . $a[$ix_ap2] . $a[$ix_nom]),
            strtoupper($b[$ix_ap1] . $b[$ix_ap2] . $b[$ix_nom])
        );
    });

    // Cabecera
    $html .= '
    <table style="width:100%; margin-bottom:0;">
      <tr>
        <td style="text-align:center;">
          <div class="titulo">LISTADO ALFABÉTICO GENERAL</div>
        </td>
        <td style="width:120px; vertical-align:top;">
          <div class="grupo-header">GRUPO</div>
          <div class="grupo-letra">'.htmlspecialchars($grupo).'</div>
        </td>
      </tr>
    </table>
    ';

    // Tabla de datos
    $html .= '
    <table class="tabla">
      <thead>
        <tr>
          <th style="width:30px;">N°</th>
          <th style="width:60px;">Codigo</th>
          <th>Postulante</th>
          <th style="width:40px;">Aula</th>
          <th style="width:60px;">Local</th>
        </tr>
      </thead>
      <tbody>
    ';

    $contador = 1;
    foreach ($registros as $row) {
        $codigo = $row[$ix_codigo];
        $aula = $row[$ix_aula];
        $local = 'UNACH'; // Valor fijo
        $apellidosNombres = strtoupper($row[$ix_ap1] . ' ' . $row[$ix_ap2] . ' ' . $row[$ix_nom]);
        // Si tienes la columna CARRERA en tu Excel, ajusta el índice:
        $ix_carrera = array_search("CARRERA", $header);
        $carrera = $ix_carrera !== false ? strtoupper($row[$ix_carrera]) : '';

        $html .= '<tr>
            <td style="text-align:center;">'.$contador.'</td>
            <td style="text-align:center;">'.$codigo.'</td>
            <td>
                '.$apellidosNombres.'<br>
                <span style="font-size:11px;">'.$carrera.'</span>
            </td>
            <td style="text-align:center;">'.$aula.'</td>
            <td style="text-align:center;">'.$local.'</td>
        </tr>
        <tr><td colspan="5" class="sep"></td></tr>
        ';
        $contador++;
    }
    $html .= '</tbody></table>';
}

// PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('listado_alfabetico_general.pdf', ['Attachment' => 0]);
?>
