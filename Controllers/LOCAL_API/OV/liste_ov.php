<?php
/**
 * API Locale : Liste & Filtres des Ordres de Virement
 * Méthode : POST uniquement
 * 
 * Actions disponibles :
 *   - search         : recherche avec filtres
 *   - get_fournisseurs
 *   - get_contrats   (nécessite fournisseur_id)
 *   - get_devises
 *   - get_natures
 *   - get_createurs
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');

// ── Sécurité ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

// ── Includes ────────────────────────────────────────────────────
require_once '../../../Classes/Database.php';

try {
    $db = (new Database())->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion BD.']);
    exit;
}

// ── Dispatch ────────────────────────────────────────────────────
$action = trim($_POST['action'] ?? '');

switch ($action) {

    // ────────────────────────────────────────────────────────────
    // RECHERCHE AVEC FILTRES
    // ────────────────────────────────────────────────────────────
    case 'search':
        $fournisseur_id = intval($_POST['fournisseur'] ?? 0);
        $contrat_id     = intval($_POST['contrat']     ?? 0);
        $devise_id      = intval($_POST['devise']      ?? 0);
        $nature_id      = intval($_POST['nature']      ?? 0);
        $createur_id    = intval($_POST['createur']    ?? 0);
        $structure      = trim($_POST['structure']     ?? '');
        $num_ov         = trim($_POST['num_ov']        ?? '');
        $num_ktp        = trim($_POST['num_ktp']       ?? '');
        $statut_code    = trim($_POST['statut']        ?? '');

        // ── Requête principale ──────────────────────────────────
    
        $params = [];

        if ($fournisseur_id > 0) {
            $sql .= " AND f.id = :fournisseur_id";
            $params[':fournisseur_id'] = $fournisseur_id;
        }
        if ($contrat_id > 0) {
            $sql .= " AND c.id = :contrat_id";
            $params[':contrat_id'] = $contrat_id;
        }
        if ($devise_id > 0) {
            $sql .= " AND m.id = :devise_id";
            $params[':devise_id'] = $devise_id;
        }
        if ($nature_id > 0) {
            $sql .= " AND n.id = :nature_id";
            $params[':nature_id'] = $nature_id;
        }
        if ($createur_id > 0) {
            // Filtrer par l'utilisateur affecté au contrat
            $sql .= " AND EXISTS (
                SELECT 1 FROM affectation a
                WHERE a.Contratid = c.id AND a.utilisateurid = :createur_id
            )";
            $params[':createur_id'] = $createur_id;
        }
        if ($num_ov !== '') {
            $sql .= " AND ov.Num_OV LIKE :num_ov";
            $params[':num_ov'] = '%' . $num_ov . '%';
        }
        if ($num_ktp !== '') {
            $sql .= " AND ov.Num_KTP LIKE :num_ktp";
            $params[':num_ktp'] = '%' . $num_ktp . '%';
        }
        if ($statut_code !== '') {
            $sql .= " AND s.code = :statut_code";
            $params[':statut_code'] = $statut_code;
        }

        $sql .= " ORDER BY ov.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Calcul des stats ────────────────────────────────────
        $total      = count($data);
        $paye       = 0;
        $en_attente = 0;
        $ce_mois    = 0;
        $moisActuel = date('Y-m');

        foreach ($data as $row) {
            if ($row['statut_code'] === 'EXEC')  $paye++;
            if ($row['statut_code'] === 'BROUI') $en_attente++;
            if (isset($row['Date_OV']) && substr($row['Date_OV'], 0, 7) === $moisActuel) $ce_mois++;
        }

        echo json_encode([
            'success' => true,
            'data'    => $data,
            'stats'   => [
                'total'      => $total,
                'paye'       => $paye,
                'en_attente' => $en_attente,
                'ce_mois'    => $ce_mois,
            ]
        ]);
        break;

    // ────────────────────────────────────────────────────────────
    // LISTES POUR LES SELECTS DE FILTRES
    // ────────────────────────────────────────────────────────────
    case 'get_fournisseurs':
        $stmt = $db->query(
            "SELECT f.id, f.code, f.Nom_Fournisseur AS nom
             FROM fournisseur f
             ORDER BY f.code ASC"
        );
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_contrats':
        $fid = intval($_POST['fournisseur_id'] ?? 0);
        if ($fid <= 0) {
            echo json_encode(['success' => false, 'message' => 'fournisseur_id requis.']);
            break;
        }
        $stmt = $db->prepare(
            "SELECT id, num_Contrat FROM Contrat WHERE Fournisseur_id = :fid ORDER BY num_Contrat ASC"
        );
        $stmt->execute([':fid' => $fid]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_devises':
        $stmt = $db->query("SELECT id, code, label FROM money ORDER BY code ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_natures':
        $stmt = $db->query("SELECT id, code, label FROM nature_ov ORDER BY label ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_createurs':
        $stmt = $db->query(
            "SELECT DISTINCT u.id,
                    CONCAT(COALESCE(u.prenom,''), ' ', u.nom) AS nom_complet
             FROM utilisateur u
             JOIN affectation a ON a.utilisateurid = u.id
             ORDER BY u.nom ASC"
        );
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ────────────────────────────────────────────────────────────
    // ACTION INCONNUE
    // ────────────────────────────────────────────────────────────
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Action inconnue: '$action'."]);
        break;
}