<?php
// Désactiver l'affichage direct des erreurs HTML pour ne pas casser le JSON
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../Classes/Database.php';
require_once __DIR__ . '/../../../Classes/Facture.php';
require_once __DIR__ . '/../../../Classes/Monnaie.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$response = [
    'success'        => false,
    'global_errors'  => [],
    'field_errors'   => [], 
    'processed_data' =>[]
];

try {
    // 1. Vérification des inputs POST
    if (!isset($_FILES['fichier_excel']) || !isset($_POST['fournisseur_id']) || !isset($_POST['contrat_id'])) {
        throw new Exception("Données manquantes (Fichier, Fournisseur ou Contrat).");
    }

    $file           = $_FILES['fichier_excel'];
    $fournisseur_id = (int) $_POST['fournisseur_id'];
    $contrat_id     = (int) $_POST['contrat_id'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erreur lors du téléchargement du fichier.");
    }

    // 2. Connexion à la base de données & Instanciation des Modèles
    $db           = (new Database())->getConnection();
    $monnaieModel = new Monnaie($db);

    // 3. Vérification de l'En-tête (Fournisseur et Contrat)
    $stmtF = $db->prepare("SELECT code FROM fournisseur WHERE id = :id");
    $stmtF->execute([':id' => $fournisseur_id]);
    $expected_code_fournisseur = trim($stmtF->fetchColumn() ?? '');

    $stmtC = $db->prepare("SELECT num_Contrat FROM contrat WHERE id = :id");
    $stmtC->execute([':id' => $contrat_id]);
    $expected_num_contrat = trim($stmtC->fetchColumn() ?? '');

    // 4. Chargement du fichier Excel
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $sheet       = $spreadsheet->getActiveSheet();

    // Lecture en-tête — template : E6 = fournisseur, F6 = contrat
    $excel_code_fournisseur = trim((string)($sheet->getCell('E6')->getValue() ?? ''));
    $excel_num_contrat      = trim((string)($sheet->getCell('F6')->getValue() ?? ''));
    
    // On met les erreurs dans 'field_errors' avec les noms des ID HTML pour cibler les DIV
    if ($excel_code_fournisseur !== $expected_code_fournisseur) {
        $response['field_errors']['fournisseur_id'] = "Code Fournisseur (<b>$excel_code_fournisseur</b>) incorrect. Attendu : $expected_code_fournisseur";
    }
    if ($excel_num_contrat !== $expected_num_contrat) {
        $response['field_errors']['contrat_id'] = "N° Contrat (<b>$excel_num_contrat</b>) incorrect. Attendu : $expected_num_contrat";
    }

    // S'il y a une erreur dans ces champs spécifiques, on renvoie la réponse et on stoppe
    if (!empty($response['field_errors'])) {
        echo json_encode($response);
        exit;
    }
    
    // 5. Lecture et Validation ligne par ligne
    // Template : données de la ligne 9 jusqu'à la dernière ligne remplie en colonne D
    $lastRow        = min($sheet->getHighestDataRow('D'), 108);
    $factures_in_excel =[];

    for ($row = 9; $row <= $lastRow; $row++) {

        // Colonne D = N° Facture
        $num_facture = trim((string)($sheet->getCell('D' . $row)->getValue() ?? ''));

        // Ignorer les lignes vides
        if (empty($num_facture)) {
            continue;
        }

        // Colonne E = Date Facture
        $date_cell = $sheet->getCell('E' . $row);
        $date_val  = $date_cell->getValue();
        $date_facture = '';

        if ($date_val !== null && $date_val !== '') {
            // CORRECTION ICI : On vérifie que la valeur est numérique avant la conversion
            // Cela empêche l'Erreur Fatale si l'utilisateur a écrit du texte dans la cellule Date.
            if (is_numeric($date_val) && Date::isDateTime($date_cell)) {
                try {
                    $date_facture = Date::excelToDateTimeObject($date_val)->format('d-m-Y');
                } catch (Exception $e) {
                    $date_facture = trim((string)$date_val);
                }
            } else {
                $date_facture = trim((string)$date_val);
            }
        }

        // Colonne F = Montant
        $montant = trim((string)($sheet->getCell('F' . $row)->getValue() ?? ''));

        // Colonne G = Monnaie
        $monnaie = trim((string)($sheet->getCell('G' . $row)->getValue() ?? ''));

        // --- DÉBUT DES VÉRIFICATIONS (Règles métiers) ---
        $row_errors =[];

        // A. Vérification de la date
        if (empty($date_facture)) {
            $row_errors[] = "Date manquante.";
        } else { 
            // 1. On remplace les potentiels tirets par des slashs pour standardiser la lecture (ex: 31-12-2024 devient 31/12/2024)
            $date_facture = str_replace('-', '/', $date_facture);

            // 2. On vérifie si ça correspond au format JJ/MM/AAAA (Day/Month/Year)
            $d_dmy = DateTime::createFromFormat('d/m/Y', $date_facture);
            
            $is_valid = $d_dmy && $d_dmy->format('d/m/Y') === $date_facture;

            if ($is_valid) {
                $date_facture = $d_dmy->format('d/m/Y'); 
            } else {
                $row_errors[] = "Date invalide ou format non reconnu (" . htmlspecialchars($date_facture) . "). Format attendu: JJ/MM/AAAA.";
            }
        }

        // B. Vérification du Montant (Doit être un float positif)
        // CORRECTION ICI : On inclut l'espace dans le remplacement pour gérer les milliers (ex: "1 000,50")
        $montant_float = (float) str_replace([' ', ','], ['', '.'], $montant);
        if ($montant_float <= 0) {
            $row_errors[] = "Montant invalide (doit être > 0).";
        }

        // C. Vérification de la Monnaie (Doit exister dans la DB)
        if (empty($monnaie)) {
            $row_errors[] = "Monnaie manquante.";
        } elseif (!$monnaieModel->codeExists($monnaie)) {
            $row_errors[] = "Monnaie '<b>$monnaie</b>' non reconnue.";
        }

        // D. Vérification Doublon DANS LE FICHIER EXCEL
        if (in_array($num_facture, $factures_in_excel)) {
            $row_errors[] = "Facture en double dans ce fichier Excel.";
        } else {
            $factures_in_excel[] = $num_facture;
        }

        // E. Vérification Doublon DANS LA BDD (Existe déjà)
        if (Facture::checkIfExists($db, $num_facture)) {
            $row_errors[] = "Cette facture existe déjà dans un autre bordereau.";
        }
        // --- FIN DES VÉRIFICATIONS ---

        $response['processed_data'][] =[
            'num_facture'  => htmlspecialchars($num_facture),
            'date_facture' => htmlspecialchars($date_facture),
            'montant'      => htmlspecialchars(number_format($montant_float, 2, ',', ' ')),
            'monnaie'      => htmlspecialchars($monnaie),
            'is_valid'     => empty($row_errors),
            'errors'       => $row_errors
        ];
    }

    // 6. Résultat final
    if (empty($response['processed_data'])) {
        $response['global_errors'][] = "Le tableau Excel est vide (Lignes 9 à $lastRow).";
    } else {
        $response['success'] = true;
    }
} catch (Exception $e) {
    $response['success']         = false;
    $response['global_errors'][] = "Erreur Système : " . $e->getMessage();
}

echo json_encode($response);
exit;