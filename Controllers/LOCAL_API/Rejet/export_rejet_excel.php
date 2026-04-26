<?php
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/Rejet.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$db = (new Database())->getConnection();

// Filters from POST or GET
$filters = [
    'fournisseur' => $_REQUEST['fournisseur'] ?? '',
    'contrat'     => $_REQUEST['contrat']     ?? '',
    'structure'   => $_REQUEST['structure']   ?? '',
    'num_rejet'   => $_REQUEST['num_rejet']   ?? '',
    'statut'      => $_REQUEST['statut']      ?? '',
];

$rejets = Rejet::getAllWithDetails($db, $filters);

// ── Build spreadsheet ─────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Liste des Rejets');

// Header row style
$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A3A5C']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
];

$headers = ['N° Rejet', 'Date', 'Fournisseur', 'Contrat', 'Créé par', 'Type Rejet', 'Statut'];
foreach ($headers as $col => $header) {
    $cell = chr(65 + $col) . '1';
    $sheet->setCellValue($cell, $header);
    $sheet->getColumnDimensionByColumn($col + 1)->setAutoSize(true);
}
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(22);

// Data rows
$row = 2;
foreach ($rejets as $r) {
    $regionCode   = strtoupper($r['region_code'] ?? 'XX');
    $numPadded    = str_pad($r['num_rejet'] ?? '0', 3, '0', STR_PAD_LEFT);
    $codification = "SH/DP/REJ/{$regionCode}/{$numPadded}";
    $date         = !empty($r['date_rejet']) ? date('d/m/Y', strtotime($r['date_rejet'])) : '—';
    $creePar      = trim(($r['createur_nom'] ?? '') . ' ' . ($r['createur_prenom'] ?? ''));

    $sheet->setCellValue("A{$row}", $codification);
    $sheet->setCellValue("B{$row}", $date);
    $sheet->setCellValue("C{$row}", $r['Nom_Fournisseur'] ?? '—');
    $sheet->setCellValue("D{$row}", $r['num_Contrat']      ?? '—');
    $sheet->setCellValue("E{$row}", $creePar               ?: '—');
    $sheet->setCellValue("F{$row}", 'REJET FOURNISSEURS ETRANGER');
    $sheet->setCellValue("G{$row}", $r['statut_label']     ?? '—');

    // Alternating row color
    if ($row % 2 === 0) {
        $sheet->getStyle("A{$row}:G{$row}")->getFill()
              ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F4FA');
    }
    $row++;
}

// ── Output ────────────────────────────────────────────────────────────
$filename = 'Liste_Rejets_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

(new Xlsx($spreadsheet))->save('php://output');
exit();