<?php
ini_set('display_errors', 0);
session_start();

// Vérification de la présence de l'ID du bordereau passé en POST au lieu de GET
if (!isset($_POST['id']) || empty($_POST['id']))
$bordereau_id = $_POST['id'];

// 1. CONNEXION ET RÉCUPÉRATION DES DONNÉES
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '../../../../Classes/Database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$database = new Database();
$bd = $database->getConnection();

// Récupération de l'en-tête DU BORDEREAU SPÉCIFIQUE
$sql_bordereau = "
    SELECT b.*, 
           c.num_Contrat, 
           fr.Nom_Fournisseur AS nom_fournisseur,
           r.code AS code_structure_head
    FROM bordereau b 
    LEFT JOIN contrat c ON b.Contrat_id = c.id 
    LEFT JOIN fournisseur fr ON c.Fournisseur_id = fr.id
    LEFT JOIN utilisateur u ON b.emeteur_id = u.id
    LEFT JOIN region_dp r ON u.region_dp_id = r.id
    WHERE b.id = :bordereau_id
";
$req_head = $bd->prepare($sql_bordereau);
$req_head->execute(['bordereau_id' => $bordereau_id]);
$head = $req_head->fetch();



// Récupération des lignes de factures avec toutes les jointures (Structure, Utilisateur...)
$sql_lignes = "
    SELECT f.*, 
           s.label AS nom_statut, 
           h.date_statuts,
           m.code AS code_monnaie,
           m.label AS nom_monnaie,
           r.code AS code_structure,
           u.nom AS nom_utilisateur,
           u.prenom AS prenom_utilisateur
    FROM facture f 
    LEFT JOIN money m ON f.money_id = m.id
    LEFT JOIN historique_facture h ON f.id = h.Factureid 
        AND h.date_statuts = (SELECT MAX(date_statuts) FROM historique_facture WHERE Factureid = f.id)
    LEFT JOIN statut_facture s ON h.statut_factureid = s.id 
    LEFT JOIN bordereau b ON f.Bordereau_id = b.id
    LEFT JOIN utilisateur u ON b.emeteur_id = u.id
    LEFT JOIN region_dp r ON u.region_dp_id = r.id
    WHERE f.Bordereau_id = :bordereau_id
";
$req_lignes = $bd->prepare($sql_lignes);
$req_lignes->execute(['bordereau_id' => $bordereau_id]);
$lignes = $req_lignes->fetchAll(PDO::FETCH_ASSOC);

// --- 2. CRÉATION DU SPREADSHEET ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Bordereau ' . ($head['num_bordereau'] ?? ''));

// Configuration visuelle de base
$spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

/* -------------------------------------------
   3. EN-TÊTE DU DOCUMENT
--------------------------------------------*/
$sheet->setCellValue('B2', 'Bordereau N° ' . ($head['num_bordereau'] ?? 'N/A')); 
$sheet->getStyle('B2')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('E11D48');

// Remplissage des informations de l'en-tête
$sheet->setCellValue('B4', 'Date Bordereau :');
$sheet->setCellValue('C4', $head['date_bordereau'] ?? 'N/A');

$sheet->setCellValue('B5', 'Fournisseur :');
$sheet->setCellValue('C5', $head['nom_fournisseur'] ?? 'N/A');

$sheet->setCellValue('B6', 'Contrat N° :');
$sheet->setCellValue('C6', $head['num_Contrat'] ?? 'N/A');

$sheet->setCellValue('G4', 'Statut :');
$sheet->setCellValue('H4', 'Transmis');

$sheet->setCellValue('G5', 'Structure Contractante :');
// Affiche la structure de la base de données, sinon "SIÈGE-DP" par défaut
$sheet->setCellValue('H5', $head['code_structure_head'] ?? 'N/A');




// Style des labels
$labelStyle = ['font' =>['bold' => true, 'color' =>['rgb' => '475569']]];
foreach(['B4', 'B5', 'B6', 'G4', 'G5', 'G6'] as $cell) {
    $sheet->getStyle($cell)->applyFromArray($labelStyle);
}

/* -------------------------------------------
   4. TABLEAU DE DONNÉES
--------------------------------------------*/
$headers =[
    'N° Facture', 'Date Facture', 
    'Montant', 'Monnaie', 'Structure', 'Traité par', 
    'Statut Facture', 'Dernier Traitement'
];
$sheet->fromArray($headers, NULL, 'B9');

// Style de l'en-tête du tableau
$tableHeaderStyle = [
    'font' =>['bold' => true, 'color' =>['rgb' => 'FFFFFF']],
    'alignment' =>['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' =>['fillType' => Fill::FILL_SOLID, 'startColor' =>['rgb' => '1E293B']],
    'borders' =>['allBorders' =>['borderStyle' => Border::BORDER_THIN, 'color' =>['rgb' => 'CBD5E1']]]
];
$sheet->getStyle('B9:I9')->applyFromArray($tableHeaderStyle);
$sheet->getRowDimension(9)->setRowHeight(25);

// Remplissage des données dynamiques
$currentRow = 10;
if (count($lignes) > 0) {
    foreach ($lignes as $row) {
        
        $traite_par = trim(($row['nom_utilisateur'] ?? '') . ' ' . ($row['prenom_utilisateur'] ?? ''));

        $sheet->setCellValue('B' . $currentRow, $row['Num_facture'] ?? '-');
        $sheet->setCellValue('C' . $currentRow, $row['date_facture'] ?? '-');
        $sheet->setCellValue('D' . $currentRow, $row['Montant'] ?? 0);
        $sheet->setCellValue('E' . $currentRow, $row['code_monnaie'] ?? $row['nom_monnaie'] ?? '-');
        $sheet->setCellValue('F' . $currentRow, $row['code_structure'] ?? '-');
        $sheet->setCellValue('G' . $currentRow, !empty($traite_par) ? $traite_par : '-');
        $sheet->setCellValue('H' . $currentRow, $row['nom_statut'] ?? '-');
        $sheet->setCellValue('I' . $currentRow, $row['date_statuts'] ?? '-');
        
        
        $sheet->getStyle('D' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $currentRow++;
    }
} else {
    $sheet->setCellValue('B10', 'Aucune facture enregistrée.');
    $sheet->mergeCells('B10:I10');
    $currentRow = 11;
}

// Style du corps du tableau
$lastRow = $currentRow - 1;
$bodyStyle =[
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' =>['borderStyle' => Border::BORDER_THIN, 'color' =>['rgb' => 'E2E8F0']]]
];
$sheet->getStyle("B10:I$lastRow")->applyFromArray($bodyStyle);

// Ajustement automatique des colonnes
foreach (range('B', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}


$safe_bordereau_num = preg_replace('/[^A-Za-z0-9_\-]/', '_', ($head['num_bordereau'] ?? 'export'));
$filename = 'Bordereau_' . $safe_bordereau_num . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;