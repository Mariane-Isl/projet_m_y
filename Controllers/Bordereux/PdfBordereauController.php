<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  CONTRÔLEUR PDF — Design identique à la photo Sonatrach         ║
 * ║  Chemin : Controllers/Bordereaux/PdfBordereauController.php      ║
 * ║  Bibliothèque : FPDF (vendor/fpdf/fpdf.php)                     ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * Design reproduit depuis la photo :
 *  - Logo Sonatrach en haut à gauche (arabe + icône + "sonatrach")
 *  - Bloc métadonnées : Destinataire, Expéditeur, N°Bordereau, Date,
 *    Contrat, Fournisseur, Émetteur, Gestionnaire
 *  - Titre centré gras : BORDEREAU D'ENVOI À LA TRÉSORERIE
 *  - Tableau bordé : N° Facture | Statut | Date | Montant | Devise
 *  - Nombre total de factures centré sous le tableau
 *  - Reçu le / Signature en bas
 *  - Fond blanc pur, sans bandes colorées
 */

session_start();

// ── FPDF ──
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';

// ── Modèles ──
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Bordereau.php';

// ════════════════════════════════════════════════
// GARDE : POST uniquement
// ════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || ($_POST['action'] ?? '') !== 'generer_pdf_bordereau') {
    header('Location: ../../Pages/Bordereaux/liste_bordereaux_recus.php');
    exit();
}

// ════════════════════════════════════════════════
// ÉTAPE 1 : Validation de l'ID
// ════════════════════════════════════════════════
$bordereau_id = isset($_POST['bordereau_id']) ? intval($_POST['bordereau_id']) : 0;

if ($bordereau_id <= 0) {
    $_SESSION['flash_message'] = "Identifiant invalide.";
    $_SESSION['flash_type']    = "danger";
    header('Location: ../../Pages/Bordereaux/liste_bordereaux_recus.php');
    exit();
}

// ════════════════════════════════════════════════
// ÉTAPE 2 : Données via les Modèles
// ════════════════════════════════════════════════
$database  = new Database();
$db        = $database->getConnection();

$bordereau = Bordereau::getByIdWithDetails($db, $bordereau_id);

if (!$bordereau) {
    $_SESSION['flash_message'] = "Bordereau introuvable.";
    $_SESSION['flash_type']    = "danger";
    header('Location: ../../Pages/Bordereaux/liste_bordereaux_recus.php');
    exit();
}

$factures = Bordereau::getFacturesWithStatutByBordereauId($db, $bordereau_id);

$nb_total       = count($factures);
$nb_trouves     = 0;
$nb_non_trouves = 0;
foreach ($factures as $fac) {
    if (!empty($fac['statut_code']) && $fac['statut_code'] !== 'TRANSMIS') {
        $nb_trouves++;
    } else {
        $nb_non_trouves++;
    }
}

// ════════════════════════════════════════════════
// ÉTAPE 3 : Helper UTF-8 → Latin-1 pour FPDF
// FPDF ne supporte pas nativement l'UTF-8.
// iconv convertit les caractères accentués.
// ════════════════════════════════════════════════
function u(string $str): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $str);
}

// ════════════════════════════════════════════════
// ÉTAPE 4 : Classe PDF personnalisée
// On étend FPDF pour définir Header() et Footer()
// qui s'appliquent automatiquement à chaque page.
// ════════════════════════════════════════════════
class BordereauPDF extends FPDF
{
    public string $numBordereau   = '';
    public string $dateGeneration = '';

    /**
     * Header() — appelé automatiquement par FPDF à chaque nouvelle page.
     * On ne met RIEN ici car la photo montre un document sans en-tête répété.
     * Le logo est dessiné manuellement une seule fois au début du contenu.
     */
    public function Header(): void
    {
        // Volontairement vide — le logo est placé manuellement (voir bas)
    }

    /**
     * Footer() — appelé automatiquement en bas de chaque page.
     * Numérotation discrète comme sur la photo.
     */
    public function Footer(): void
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(160, 160, 160);
        // Numéro de page centré, discret
        $this->Cell(0, 5, u('Page ' . $this->PageNo() . '/{nb}   —   ' . $this->dateGeneration . '   —   Usage interne'), 0, 0, 'C');
    }
}

// ════════════════════════════════════════════════
// ÉTAPE 5 : Initialisation du document
// ════════════════════════════════════════════════
$pdf = new BordereauPDF('P', 'mm', 'A4');
$pdf->numBordereau   = $bordereau['num_bordereau'] ?? '';
$pdf->dateGeneration = date('d/m/Y H:i');

$pdf->AliasNbPages();                    // Active le remplacement de {nb}
$pdf->SetMargins(18, 15, 18);           // Marges larges comme la photo
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// Largeur utile (210mm - 18 - 18 = 174mm)
$W = 174;

// ════════════════════════════════════════════════
// SECTION A : LOGO SONATRACH (haut gauche)
// Reproduction exacte de la photo :
//   - Texte arabe "سوناطراك"
//   - Icône orange (rectangle avec lignes, ou image)
//   - Texte latin "sonatrach" en minuscules
// ════════════════════════════════════════════════

$logoPath = realpath(__DIR__ . '/../../dist/images/sonatrach.jpg');

if ($logoPath && file_exists($logoPath)) {
    // ── Cas 1 : L'image logo existe → on l'affiche ──
    $pdf->Image($logoPath, 18, 15, 30); // x=18, y=15, largeur=30mm (hauteur auto)
    $yAfterLogo = max($pdf->GetY(), 15 + 30);
    $pdf->SetY($yAfterLogo + 4);

} else {
    // ── Cas 2 : Pas d'image → on dessine le logo manuellement avec FPDF ──

    $startX = 18;
    $startY = 15;

    // Texte arabe "سوناطراك" (converti en latin car FPDF ne supporte pas l'arabe natif)
    // On affiche le nom en translittération ou en français
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(40, 40, 40);
    $pdf->SetXY($startX, $startY);
    $pdf->Cell(35, 5, u('sonatrach'), 0, 1, 'L');

    // Rectangle orange principal (corps du logo Sonatrach — 3 lignes horizontales)
    $ox = $startX;
    $oy = $startY + 6;

    // Fond orange du logo
    $pdf->SetFillColor(229, 114, 15);   // Orange Sonatrach
    $pdf->Rect($ox, $oy, 28, 20, 'F'); // Grand rectangle orange

    // Lignes blanches horizontales (motif du logo)
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect($ox + 2, $oy + 3,  24, 2.5, 'F'); // Ligne 1
    $pdf->Rect($ox + 2, $oy + 8,  24, 2.5, 'F'); // Ligne 2
    $pdf->Rect($ox + 2, $oy + 13, 24, 2.5, 'F'); // Ligne 3

    // Bord gauche blanc (séparateur vertical du logo)
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect($ox, $oy, 1.5, 20, 'F');

    // Texte "sonatrach" en dessous du logo
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(40, 40, 40);
    $pdf->SetXY($ox, $oy + 22);
    $pdf->Cell(28, 5, 'sonatrach', 0, 1, 'C');

    $pdf->SetY($oy + 22 + 6);
}

// ════════════════════════════════════════════════
// SECTION B : MÉTADONNÉES (comme la photo)
// Reproduit exactement :
//   Destinataire :  SH/DP/SIEGE/...
//   Expéditeur :    [région]
//   N° Bordereau :  XXX         Date Bordereau: YYY
//   Contrat N° :    XXX         Fournisseur:    YYY
//   Émetteur :      XXX
//   Gestionnaire :  XXX
// ════════════════════════════════════════════════

$pdf->Ln(2);

// Préparation des données
$emetteur      = trim(($bordereau['emetteur_nom'] ?? '') . ' ' . ($bordereau['emetteur_prenom'] ?? ''));
$date_brd      = !empty($bordereau['date_bordereau']) ? date('d/m/Y', strtotime($bordereau['date_bordereau'])) : '—';
$num_brd       = $bordereau['num_bordereau']  ?? '—';
$num_cnt       = $bordereau['num_contrat']    ?? '—';
$fournisseur   = $bordereau['nom_Fournisseur'] ?? '—';
$region        = $bordereau['region_label']   ?? '—';
$statutLabel   = $bordereau['statut_label']   ?? '—';

// Colonne label = 32mm, valeur = le reste
$lblW = 32;
$valW = $W - $lblW;

// ── Ligne : Destinataire ──
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell($lblW, 7, u('Destinataire :'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($valW, 7, u('SH/DP/SIEGE/Direction Finance & Comptabilite/Service Tresorerie Devises'), 0, 1, 'L');

// ── Ligne : Expéditeur ──
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($lblW, 7, u('Expediteur :'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($valW, 7, u($region), 0, 1, 'L');

// ── Ligne : N° Bordereau + Date (sur la même ligne, 2 colonnes) ──
$halfW = $W / 2;
$subLblW = 32;
$subValW = $halfW - $subLblW;

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($subLblW, 7, u('N° Bordereau :'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($subValW, 7, u($num_brd), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($subLblW, 7, u('Date Bordereau:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, u($date_brd), 0, 1, 'L');

// ── Ligne : Contrat + Fournisseur (sur la même ligne) ──
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($subLblW, 7, u('Contrat N° :'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($subValW, 7, u($num_cnt), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($subLblW, 7, u('Fournisseur:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, u($fournisseur), 0, 1, 'L');

// ── Ligne : Émetteur ──
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($lblW, 7, u('Emetteur :'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($valW, 7, u($emetteur ?: '—'), 0, 1, 'L');

// ── Ligne : Gestionnaire ──
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($lblW, 7, u('Gestionnaire :'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($valW, 7, u($emetteur ?: '—'), 0, 1, 'L');

// ── Ligne : Statut (optionnel, discret) ──
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($lblW, 7, u('Statut :'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($valW, 7, u($statutLabel), 0, 1, 'L');

$pdf->Ln(4);

// ════════════════════════════════════════════════
// SECTION C : TITRE CENTRAL
// Exactement comme la photo : centré, gras, grande taille
// ════════════════════════════════════════════════
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetTextColor(20, 20, 20);
$pdf->Cell($W, 10, u('BORDEREAU RECEPTIONNER'), 0, 1, 'C');
$pdf->Ln(3);

// ════════════════════════════════════════════════
// SECTION D : TABLEAU DES FACTURES
// Design identique à la photo :
//   - Bordures noires
//   - En-tête : fond léger, texte centré
//   - Colonnes : N° Facture | Statut | Date | Montant | Devise
//   - Lignes alternées blanc/gris très léger
// ════════════════════════════════════════════════

// Largeurs des colonnes (total = $W = 174mm)
$cNumFac  = 42;   // N° Facture
$cStatut  = 50;   // Statut (remplace N° Enregistrement)
$cDate    = 25;   // Date
$cMontant = 35;   // Montant
$cDevise  = 22;   // Devise
// Total = 42+50+25+35+22 = 174 ✓

$rowH = 7; // Hauteur de chaque ligne

// ── En-tête du tableau ──
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(30, 30, 30);
$pdf->SetFillColor(235, 235, 235);    // Gris très léger pour l'en-tête
$pdf->SetDrawColor(80, 80, 80);       // Bordure gris foncé
$pdf->SetLineWidth(0.3);

$pdf->Cell($cNumFac,  $rowH, u('N° Facture'),       'BLRT', 0, 'C', true);
$pdf->Cell($cStatut,  $rowH, u('Statut'),            'BRT',  0, 'C', true);
$pdf->Cell($cDate,    $rowH, u('Date'),              'BRT',  0, 'C', true);
$pdf->Cell($cMontant, $rowH, u('Montant'),           'BRT',  0, 'C', true);
$pdf->Cell($cDevise,  $rowH, u('Devise'),            'BRT',  1, 'C', true);

// ── Lignes du tableau ──
$pdf->SetFont('Arial', '', 9);

if (empty($factures)) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(130, 130, 130);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell($W, $rowH, u('Aucune facture attachee a ce bordereau.'), 'LRB', 1, 'C', true);
} else {
    foreach ($factures as $i => $fac) {
        $estTrouvee  = !empty($fac['statut_code']) && $fac['statut_code'] !== 'TRANSMIS';
        $statutLabel = $fac['statut_label'] ?? null;
        $date_fac    = !empty($fac['date_facture'])  ? date('d/m/Y', strtotime($fac['date_facture']))  : '—';
        $date_stat   = !empty($fac['date_statut'])   ? date('d/m/Y', strtotime($fac['date_statut']))   : '—';
        $montant     = $fac['Montant'] !== null ? number_format((float)$fac['Montant'], 2, '.', ' ') : '—';
        $devise      = $fac['devise'] ?? '—';
        $numFac      = $fac['Num_facture'] ?? '—';

        // Alternance de fond : blanc / gris très léger
        if ($i % 2 === 0) {
            $pdf->SetFillColor(255, 255, 255);
        } else {
            $pdf->SetFillColor(248, 248, 248);
        }
        $pdf->SetTextColor(30, 30, 30);

        // N° Facture — centré
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell($cNumFac, $rowH, u($numFac), 'LRB', 0, 'C', true);

        // Statut — avec couleur de texte selon état
        if ($estTrouvee) {
            $pdf->SetTextColor(6, 95, 70);    // Vert foncé
            $pdf->SetFont('Arial', 'B', 8);
            $statTxt = u($statutLabel ?? 'Trouve');
        } else {
            $pdf->SetTextColor(153, 27, 27);  // Rouge foncé
            $pdf->SetFont('Arial', 'B', 8);
            $statTxt = u('Non trouvee');
        }
        $pdf->Cell($cStatut, $rowH, $statTxt, 'RB', 0, 'C', true);

        // Date de la facture
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell($cDate, $rowH, u($date_fac), 'RB', 0, 'C', true);

        // Montant — aligné à droite
        $pdf->Cell($cMontant, $rowH, u($montant), 'RB', 0, 'R', true);

        // Devise — centré
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell($cDevise, $rowH, u($devise), 'RB', 1, 'C', true);
    }
}

// ── Fermeture du tableau (ligne du bas) ──
// La dernière cellule a déjà 'B' dans la boucle,
// on ajoute juste une ligne de séparation visuelle.
$pdf->SetDrawColor(80, 80, 80);
$pdf->SetFillColor(255, 255, 255);

$pdf->Ln(3);

// ════════════════════════════════════════════════
// SECTION E : NOMBRE TOTAL (centré, gras)
// Exactement comme la photo
// ════════════════════════════════════════════════
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(20, 20, 20);
$pdf->Cell($W, 8,
    u('Nombre total de factures : ' . $nb_total .
      '   (' . $nb_trouves . ' trouvee(s) / ' . $nb_non_trouves . ' non trouvee(s))'),
    0, 1, 'C'
);

$pdf->Ln(8);

// ════════════════════════════════════════════════
// SECTION F : SIGNATURES (comme la photo)
// "Reçu le :" et "Signature :" sur des lignes séparées
// ════════════════════════════════════════════════
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(20, 20, 20);

$pdf->Cell($W, 7, u('Recu le :  ___________________________'), 0, 1, 'L');
$pdf->Ln(3);
$pdf->Cell($W, 7, u('Signature :  ___________________________'), 0, 1, 'L');

// ════════════════════════════════════════════════
// ÉTAPE 6 : Envoi du PDF au navigateur
// 'D' = force le téléchargement (Download)
// 'I' = affiche en ligne (Inline)
// ════════════════════════════════════════════════
$filename = 'bordereau_'
    . preg_replace('/[^A-Za-z0-9\-\_]/', '_', $bordereau['num_bordereau'] ?? 'export')
    . '.pdf';

$pdf->Output('D', $filename);
exit();
