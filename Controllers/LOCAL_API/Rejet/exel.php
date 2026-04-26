<?php
// === 1. AFFICHER LES ERREURS POUR DÉBOGUER ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Vérification des filtres passés en POST
if (empty($_POST['fournisseur_id']) || empty($_POST['contrat_id']) || empty($_POST['structure_id'])) {
    die("Erreur : Paramètres manquants pour l'export Excel.");
}

$fournisseur_id = $_POST['fournisseur_id'];
$contrat_id = $_POST['contrat_id'];
$structure_id = $_POST['structure_id'];

// 3. INCLUSIONS AVEC LES BONS CHEMINS 
// (On remonte de 3 niveaux : LOCAL_API -> Controllers -> projet -> vendor/classes)
require_once __DIR__ . '/../../../vendor/autoload.php'; 
require_once __DIR__ . '/../../../classes/Database.php';              
require_once __DIR__ . '/../../../classes/Rejet.php';                 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$database = new Database();
$db = $database->getConnection();

// 4. Récupération des données via la fonction existante
$lignes = Rejet::getFacturesRejetables($db, $fournisseur_id, $contrat_id, $structure_id);

// Récupérer les infos d'en-tête
$nom_fournisseur = 'N/A';
$num_contrat = 'N/A';
$nom_structure = 'N/A';

if (count($lignes) > 0) {
    $nom_fournisseur = $lignes[0]['Nom_Fournisseur'];
    $num_contrat = $lignes[0]['num_Contrat'];
    $nom_structure = $lignes[0]['nom_structure'];
}

// 5. CRÉATION DU SPREADSHEET
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Factures Rejetables');

// Configuration visuelle de base
$spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

/* --- EN-TÊTE DU DOCUMENT --- */
$sheet->setCellValue('B2', 'Factures Éligibles au Rejet'); 
$sheet->getStyle('B2')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('E11D48');

// Remplissage des informations de l'en-tête
$sheet->setCellValue('B4', 'Date Export :');
$sheet->setCellValue('C4', date('d/m/Y H:i'));

$sheet->setCellValue('B5', 'Fournisseur :');
$sheet->setCellValue('C5', $nom_fournisseur);

$sheet->setCellValue('G4', 'Contrat N° :');
$sheet->setCellValue('H4', $num_contrat);

$sheet->setCellValue('G5', 'Structure :');
$sheet->setCellValue('H5', $nom_structure);

// Style des labels
$labelStyle = ['font' =>['bold' => true, 'color' =>['rgb' => '475569']]];
foreach(['B4', 'B5', 'G4', 'G5'] as $cell) {
    $sheet->getStyle($cell)->applyFromArray($labelStyle);
}

/* --- TABLEAU DE DONNÉES --- */
$headers =[
    'N° Facture', 'Date Facture', 'Montant', 
    'Monnaie', 'Contrat', 'Fournisseur', 'Structure', 'Statut'
];
$sheet->fromArray($headers, NULL, 'B8');

// Style de l'en-tête du tableau
$tableHeaderStyle = [
    'font' =>['bold' => true, 'color' =>['rgb' => 'FFFFFF']],
    'alignment' =>['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' =>['fillType' => Fill::FILL_SOLID, 'startColor' =>['rgb' => '1E293B']],
    'borders' =>['allBorders' =>['borderStyle' => Border::BORDER_THIN, 'color' =>['rgb' => 'CBD5E1']]]
];
$sheet->getStyle('B8:I8')->applyFromArray($tableHeaderStyle);
$sheet->getRowDimension(8)->setRowHeight(25);

// Remplissage des données dynamiques
$currentRow = 9;
if (count($lignes) > 0) {
    foreach ($lignes as $row) {
        $sheet->setCellValue('B' . $currentRow, $row['Num_facture'] ?? '-');
        $sheet->setCellValue('C' . $currentRow, !empty($row['date_facture']) ? date('d/m/Y', strtotime($row['date_facture'])) : '-');
        $sheet->setCellValue('D' . $currentRow, $row['Montant'] ?? 0);
        $sheet->setCellValue('E' . $currentRow, $row['monnaie'] ?? '-');
        $sheet->setCellValue('F' . $currentRow, $row['num_Contrat'] ?? '-');
        $sheet->setCellValue('G' . $currentRow, $row['Nom_Fournisseur'] ?? '-');
        $sheet->setCellValue('H' . $currentRow, $row['nom_structure'] ?? '-');
        $sheet->setCellValue('I' . $currentRow, $row['statut'] ?? '-');
        
        $sheet->getStyle('D' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $currentRow++;
    }
} else {
    $sheet->setCellValue('B9', 'Aucune facture éligible au rejet.');
    $sheet->mergeCells('B9:I9');
    $currentRow = 10;
}

// Style du corps du tableau
$lastRow = $currentRow - 1;
$bodyStyle =[
    'alignment' =>['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' =>['allBorders' =>['borderStyle' => Border::BORDER_THIN, 'color' =>['rgb' => 'E2E8F0']]]
];
$sheet->getStyle("B9:I$lastRow")->applyFromArray($bodyStyle);

// Ajustement automatique des colonnes
foreach (range('B', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 6. TÉLÉCHARGEMENT DU FICHIER
$filename = 'Rejets_Possibles_' . date('Ymd_Hi') . '.xlsx';

// Important : on vide tout buffer de sortie qui aurait pu être généré avant pour éviter de corrompre le fichier
if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;