<?php
// CHANGEZ CETTE LIGNE TEMPORAIREMENT POUR VOIR L'ERREUR
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../Classes/Database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$database = new Database();
$db = $database->getConnection();

// 1. RÉCUPÉRATION DES FILTRES DE RECHERCHE (envoyés en POST)
$fournisseur_id = $_POST['fournisseur_id'] ?? 'Tous';
$contrat_id     = $_POST['contrat_id'] ?? 'Tous';
$structure_id   = $_POST['structure_id'] ?? 'Toutes';
$num_rejet      = $_POST['num_rejet'] ?? '';
$statut         = $_POST['statut'] ?? 'Toutes';

// 2. REQUÊTE SQL (Adaptée pour récupérer exactement les colonnes du tableau)
$sql = "SELECT r.id, 
               r.num_rejet, 
               hr.date_rejet as date_statut,
               fr.Nom_Fournisseur as fournisseur, 
               c.num_Contrat as contrat,
               CONCAT(u.nom, ' ', u.prenom) as cree_par,
               r.cause as type_rejet,
               sr.label as statut_actuel
        FROM rejet r
        LEFT JOIN contrat c ON r.Contratid = c.id
        LEFT JOIN fournisseur fr ON c.Fournisseur_id = fr.id
        LEFT JOIN region_dp reg ON r.region_dpid = reg.id
        LEFT JOIN (
            SELECT Rejetid, MAX(date_rejet) AS max_date
            FROM historique_rejet GROUP BY Rejetid
        ) last_h ON r.id = last_h.Rejetid
        LEFT JOIN historique_rejet hr ON hr.Rejetid = r.id AND hr.date_rejet = last_h.max_date
        LEFT JOIN statut_rejet sr ON hr.statut_rejetid = sr.id
        LEFT JOIN facture_rejer frj ON frj.Rejetid = r.id
        LEFT JOIN facture fac ON frj.Factureid = fac.id
        LEFT JOIN bordereau b ON fac.Bordereau_id = b.id
        LEFT JOIN utilisateur u ON b.emeteur_id = u.id
        WHERE 1=1";

$params =[];

if ($fournisseur_id !== 'Tous' && !empty($fournisseur_id)) {
    $sql .= " AND fr.id = :fournisseur_id";
    $params[':fournisseur_id'] = $fournisseur_id;
}
if ($contrat_id !== 'Tous' && !empty($contrat_id)) {
    $sql .= " AND c.id = :contrat_id";
    $params[':contrat_id'] = $contrat_id;
}
if ($structure_id !== 'Toutes' && !empty($structure_id)) {
    $sql .= " AND r.region_dpid = :structure_id";
    $params[':structure_id'] = $structure_id;
}
if (!empty($num_rejet)) {
    $sql .= " AND r.num_rejet LIKE :num_rejet";
    $params[':num_rejet'] = '%' . $num_rejet . '%';
}
if ($statut !== 'Toutes' && !empty($statut)) {
    $sql .= " AND sr.label = :statut";
    $params[':statut'] = $statut;
}

// Group By pour éviter les doublons si un rejet a plusieurs factures
$sql .= " GROUP BY r.id ORDER BY r.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 3. CRÉATION DU SPREADSHEET ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Liste des Rejets');

// Configuration visuelle de base
$spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

/* -------------------------------------------
   4. EN-TÊTE DU DOCUMENT
--------------------------------------------*/
$sheet->setCellValue('B2', 'LISTE DES REJETS DE FACTURES'); 
$sheet->getStyle('B2')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('00C3FF'); // Bleu de votre thème

$sheet->setCellValue('B4', 'Date d\'export :');
$sheet->setCellValue('C4', date('d/m/Y H:i'));
$sheet->getStyle('B4')->getFont()->setBold(true);

/* -------------------------------------------
   5. TABLEAU DE DONNÉES
--------------------------------------------*/
$headers =[
    'N° Rejet', 'Date', 'Fournisseur', 'Contrat', 
    'Créé par', 'Type Rejet', 'Statut'
];
$sheet->fromArray($headers, NULL, 'B7');

// Style de l'en-tête du tableau (Thème sombre)
$tableHeaderStyle =[
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' =>['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' =>['rgb' => '15191D']], // Couleur de vos cards
    'borders' =>['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' =>['rgb' => '495057']]]
];
$sheet->getStyle('B7:H7')->applyFromArray($tableHeaderStyle);
$sheet->getRowDimension(7)->setRowHeight(25);

// Remplissage des données dynamiques
$currentRow = 8;
if (count($lignes) > 0) {
    foreach ($lignes as $row) {
        $sheet->setCellValue('B' . $currentRow, $row['num_rejet'] ?? '-');
        $sheet->setCellValue('C' . $currentRow, $row['date_statut'] ?? '-');
        $sheet->setCellValue('D' . $currentRow, $row['fournisseur'] ?? 'Non défini');
        $sheet->setCellValue('E' . $currentRow, $row['contrat'] ?? '-');
        $sheet->setCellValue('F' . $currentRow, trim($row['cree_par']) ?: 'Non défini');
        $sheet->setCellValue('G' . $currentRow, $row['type_rejet'] ?? '-');
        $sheet->setCellValue('H' . $currentRow, $row['statut_actuel'] ?? '-');
        
        $currentRow++;
    }
} else {
    $sheet->setCellValue('B8', 'Aucun rejet trouvé avec ces filtres.');
    $sheet->mergeCells('B8:H8');
    $currentRow = 9;
}

// Style du corps du tableau
$lastRow = $currentRow - 1;
$bodyStyle =[
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' =>['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' =>['rgb' => 'CBD5E1']]]
];
$sheet->getStyle("B8:H$lastRow")->applyFromArray($bodyStyle);

// Ajustement automatique des colonnes
foreach (range('B', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 6. GÉNÉRATION ET TÉLÉCHARGEMENT DU FICHIER
$filename = 'Liste_Rejets_Factures_' . date('Ymd_Hi') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;