<?php
ini_set('display_errors', 0);
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;


$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Bordereau');

// ============================================================
// 1. CONFIGURATION "CANVAS"
// ============================================================
$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
$spreadsheet->getDefaultStyle()->getFont()->setSize(10);

// Fond global gris très clair
$sheet->getStyle('A1:Z120')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('F0F4F8');

// Masquer les lignes de grille
$sheet->setShowGridlines(false);

// ============================================================
// 2. COLONNES : largeurs
// ============================================================
$sheet->getColumnDimension('A')->setWidth(1.5);  // bande décorative
$sheet->getColumnDimension('B')->setWidth(2);    // marge gauche vide
$sheet->getColumnDimension('C')->setWidth(2);    // marge gauche vide
$sheet->getColumnDimension('D')->setWidth(28);   // N°FACTURE
$sheet->getColumnDimension('E')->setWidth(22);   // DATE FACTURE
$sheet->getColumnDimension('F')->setWidth(22);   // MONTANT
$sheet->getColumnDimension('G')->setWidth(15);   // MONNAIE
$sheet->getColumnDimension('H')->setWidth(2);    // marge droite vide

// ============================================================
// 3. LIGNES DE PADDING HAUT (Lignes 1, 2, 3 bien séparées)
// ============================================================
$sheet->getRowDimension(1)->setRowHeight(15);   // marge haute vide
$sheet->getRowDimension(2)->setRowHeight(50);   // header titre
$sheet->getRowDimension(3)->setRowHeight(5);    // bande accent
$sheet->getRowDimension(4)->setRowHeight(18);   // espace avant cartes
$sheet->getRowDimension(5)->setRowHeight(14);   // label cartes
$sheet->getRowDimension(6)->setRowHeight(30);   // valeur cartes
$sheet->getRowDimension(7)->setRowHeight(18);   // espace avant tableau
$sheet->getRowDimension(8)->setRowHeight(32);   // en-tête tableau

// ============================================================
// 2. BANDE DÉCORATIVE LATÉRALE (colonne A — toute hauteur)
// ============================================================
$sheet->getStyle('A1:A120')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('1A56DB');

// ============================================================
// 3. HEADER PRINCIPAL (Ligne 2)
// ============================================================

// Fond du header — commence à D pour laisser B et C comme marges
$sheet->getStyle('D2:H2')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('1A56DB');

// Titre
$sheet->mergeCells('D2:H2');
$sheet->setCellValue('D2', '  BORDEREAU DE FACTURATION');
$sheet->getStyle('D2')->getFont()
    ->setName('Calibri')
    ->setSize(16)
    ->setBold(true)
    ->getColor()->setRGB('FFFFFF');
$sheet->getStyle('D2')->getAlignment()
    ->setVertical(Alignment::VERTICAL_CENTER)
    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Bande accent (ligne 3)
$sheet->getStyle('D3:H3')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('1748C0');

// ============================================================
// 4. CARTES D'INFORMATION (Lignes 5–6)
// ============================================================

// Labels ligne 5
$sheet->setCellValue('E5', 'CODE FOURNISSEUR');
$sheet->setCellValue('F5', 'N° CONTRAT');
$labelStyle = [
    'font' => [
        'name'  => 'Calibri',
        'size'  => 7.5,
        'bold'  => true,
        'color' => ['rgb' => '6B7A99'],
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
];
$sheet->getStyle('E5:F5')->applyFromArray($labelStyle);

// Valeurs ligne 6 (C5 et D5 dans le validateur → E6 et F6 ici)
$inputStyle = [
    'font' => [
        'name'  => 'Calibri',
        'size'  => 12,
        'bold'  => true,
        'color' => ['rgb' => '0D1B3E'],
    ],
    'fill' => [
        'fillType'   => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFFFFF'],
    ],
    'borders' => [
        'left'   => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1A56DB']],
        'right'  => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'D1D9E6']],
        'top'    => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'D1D9E6']],
        'bottom' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'D1D9E6']],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical'   => Alignment::VERTICAL_CENTER,
        'indent'     => 1,
    ],
];
$sheet->getStyle('E6')->applyFromArray($inputStyle);
$sheet->getStyle('F6')->applyFromArray($inputStyle);

// ============================================================
// 5. EN-TÊTE DU TABLEAU (Ligne 8) — commence à D
// ============================================================
$headers = ['N°FACTURE', 'DATE FACTURE', 'MONTANT', 'MONNAIE'];
$sheet->fromArray($headers, NULL, 'D8');

$tableHeaderStyle = [
    'font' => [
        'name'  => 'Calibri',
        'size'  => 9.5,
        'bold'  => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType'   => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1A56DB'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '3B6FE8']],
    ],
];
$sheet->getStyle('D8:G8')->applyFromArray($tableHeaderStyle);

// Ligne accent dorée sous l'en-tête
$sheet->getStyle('D8:G8')->getBorders()->getBottom()
    ->setBorderStyle(Border::BORDER_MEDIUM)
    ->getColor()->setRGB('0D3DB5');

// N°FACTURE aligné à gauche
$sheet->getStyle('D8')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
    ->setIndent(1);

// ============================================================
// 6. CORPS DU TABLEAU (Lignes 9–108) — D à G
// ============================================================
$lastRow = 108;

// Bordures communes à inclure dans chaque ligne
$rowBorders = [
    'bottom'  => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CBD5E1']],
    'top'     => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CBD5E1']],
    'left'    => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CBD5E1']],
    'right'   => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CBD5E1']],
];

// Styles alternés avec bordures incluses
$styleRowNormal = [
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
    'font'      => ['name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => '1E293B']],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders'   => $rowBorders,
];

$styleLignePaire = [
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF1FB']],
    'font'      => ['name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => '1E293B']],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders'   => $rowBorders,
];

for ($i = 9; $i <= $lastRow; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(22);

    if ($i % 2 == 0) {
        $sheet->getStyle("D$i:G$i")->applyFromArray($styleLignePaire);
    } else {
        $sheet->getStyle("D$i:G$i")->applyFromArray($styleRowNormal);
    }

    $sheet->getStyle("D$i")->getFont()->setBold(true);
    $sheet->getStyle("D$i")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
        ->setIndent(1);
}

// Bordure extérieure du tableau par-dessus
$sheet->getStyle("D8:G$lastRow")->getBorders()->getOutline()
    ->setBorderStyle(Border::BORDER_MEDIUM)
    ->getColor()->setRGB('94A3B8');

// ============================================================
// 7. FILTRE AUTOMATIQUE
// ============================================================
$sheet->setAutoFilter('D8:G8');

// ============================================================
// EXPORT
// ============================================================
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Modéle_Bordereau.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;