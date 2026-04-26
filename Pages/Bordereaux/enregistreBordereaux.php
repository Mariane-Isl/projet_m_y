<?php
session_start();

$page_title = "BORDEREAU";

require_once '../../Classes/Database.php';
require_once '../../Classes/bordereau.php';
require_once '../../Classes/historique_borderau.php';

$database = new Database();
$bd = $database->getConnection();


// --- ON VÉRIFIE L'ID SANS ARRÊTER LA PAGE ---
$erreur_id = false;
if (isset($_POST['bordereau_id']) && !empty($_POST['bordereau_id'])) {
    $bordereau_id = $_POST['bordereau_id'];
} else {
    $erreur_id = true;
}


$brd = historique_borderau::getDetailsBordereau($bd, $bordereau_id);


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
$lignes = $req_lignes->fetchAll();


?>

<?php include '../includes/header.php'; ?>

<!-- Inclusion de la Sidebar -->
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

    <!-- Inclusion de la Topbar -->
    <?php include '../includes/topbar.php'; ?>

    <div class="content-area">


        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Bordereau N° <span
                        class="text-danger"><?php echo htmlspecialchars($brd['num_bordereau'] ?? 'N/A'); ?></span></h4>
                <div>
                    <a class="btn btn-primary btn-sm">
                        <i class="fa fa-print"></i> Imprimer le Bordereau
                    </a>
                    <a href="../../Controllers/LOCAL_API/Bordereaux/exel.php" class="btn btn-success btn-sm">
                        <i class="fa fa-file-excel"></i> Exporter en Excel
                    </a>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 50px;">

                <!-- Colonne gauche : Infos générales -->
                <div>
                    <p
                        style="font-size:0.73rem;color:var(--accent-blue);margin-bottom:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">
                        Informations Générales
                    </p>
                    <table style="width:100%;border-collapse:collapse;font-size:0.83rem;">
                        <tr>
                            <td style="color:var(--text-muted);padding:6px 0;width:140px;">
                                <span style="display:flex;align-items:center;gap:5px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M3 9h2V7H3v2zm0 4h2v-2H3v2zm0 4h2v-2H3v2zm4-8h11V7H7v2zm0 4h11v-2H7v2zm0 4h11v-2H7v2z" />
                                    </svg>
                                    N° Bordereau:
                                </span>
                            </td>
                            <!-- OK -->
                            <td style="font-weight:600;"><?= htmlspecialchars($brd['num_bordereau'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td style="color:var(--text-muted);padding:6px 0;">
                                <span style="display:flex;align-items:center;gap:5px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M19 4h-1V2h-2v2H8V2H6v2H5C3.9 4 3 4.9 3 6v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z" />
                                    </svg>
                                    Date Bordereau:
                                </span>
                            </td>
                            <!-- OK -->
                            <td style="font-weight:600;">
                                <?= !empty($brd['date_bordereau']) ? htmlspecialchars(date('d/m/Y', strtotime($brd['date_bordereau']))) : '—' ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="color:var(--text-muted);padding:6px 0;">
                                <span style="display:flex;align-items:center;gap:5px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                                    </svg>
                                    Contrat N°:
                                </span>
                            </td>
                            <!-- CORRIGÉ : Attention à la majuscule "C" de num_Contrat tel que défini dans le SQL -->
                            <td style="font-weight:600;"><?= htmlspecialchars($brd['num_Contrat'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td style="color:var(--text-muted);padding:6px 0;">
                                <span style="display:flex;align-items:center;gap:5px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 7V3H2v18h20V7H12z" />
                                    </svg>
                                    Fournisseur:
                                </span>
                            </td>
                            <!-- CORRIGÉ : Attention aux majuscules "N" et "F" de Nom_Fournisseur tel que défini dans le SQL -->
                            <td style="font-weight:600;"><?= htmlspecialchars($brd['Nom_Fournisseur'] ?? '—') ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Colonne droite : Infos structurelles -->
                <div style="border-left:1px solid rgba(255,255,255,0.07);padding-left:40px;">
                    <p
                        style="font-size:0.73rem;color:var(--accent-blue);margin-bottom:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">
                        Informations Structurelles &amp; Statut
                    </p>
                    <table style="width:100%;border-collapse:collapse;font-size:0.83rem;">
                        <tr>
                            <td style="color:var(--text-muted);padding:6px 0;width:130px;">
                                <span style="display:flex;align-items:center;gap:5px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 7V3H2v18h20V7H12z" />
                                    </svg>
                                    Structure:
                                </span>
                            </td>
                            <td style="font-weight:600;">
                                <?= htmlspecialchars($brd['region_label'] ?? 'Non assignée') ?>
                            </td>
                        </tr>
                        <tr>

                            <td style="color:var(--text-muted);padding:6px 0;width:130px;">
                                <span style="display:flex;align-items:center;gap:5px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 7V3H2v18h20V7H12z" />
                                    </svg>
                                    Emmeteur:
                                </span>
                            </td>
                            <td style="font-weight:600;">
                                <?= htmlspecialchars($brd['emetteur_nom'] . " " . $brd['emetteur_prenom'] ?? '—') ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="color:var(--text-muted);padding:6px 0;">
                                <span style="display:flex;align-items:center;gap:5px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" />
                                    </svg>
                                    Statut Actuel:
                                </span>
                            </td>
                            <td>
                                <!-- CORRIGÉ : Affichage dynamique depuis $brd['statut_label'] au lieu de $statutDisplay -->
                                <span
                                    style="background:<?= $badgeStatutBg ?? '#555' ?>;color:#fff;padding:3px 12px;border-radius:4px;font-size:0.77rem;font-weight:700;">
                                    <?= htmlspecialchars($brd['statut_label'] ?? 'Aucun historique') ?>
                                </span>
                            </td>
                        </tr>

                        <!-- CORRIGÉ : Affichage de la date la plus récente grâce à $brd['derniere_date_statut'] -->
                        <?php if (!empty($brd['derniere_date_statut'])): ?>
                        <tr>
                            <td style="color:var(--text-muted);padding:6px 0;font-size:0.8rem;">Date du Statut:</td>
                            <td style="color:var(--accent-green);font-weight:600;font-size:0.81rem;">
                                ✅ <?= htmlspecialchars(date('d/m/Y H:i', strtotime($brd['derniere_date_statut']))) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered bg-white text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>N° Facture</th>
                            <th>Date Facture</th>
                            <th>Montant</th>
                            <th>Monnaie</th>
                            <th>Structure</th>
                            <th>Traité par</th>
                            <th>Statut Facture</th>
                            <th>Dernier Traitement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($lignes) > 0): ?>
                        <?php foreach ($lignes as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Num_facture'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['date_facture'] ?? '-'); ?></td>
                            <td><?php echo number_format($row['Montant'] ?? 0, 2, ',', ' '); ?></td>
                            <td><?php echo htmlspecialchars($row['code_monnaie'] ?? $row['nom_monnaie'] ?? $row['money_id'] ?? '-'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['code_structure'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(trim(($row['nom_utilisateur'] ?? '') . ' ' . ($row['prenom_utilisateur'] ?? '')) ?: '-'); ?>
                            </td>
                            <td>
                                <?php if (!empty($row['nom_statut'])): ?>
                                <span class="badge bg-info text-dark">
                                    <?php echo htmlspecialchars($row['nom_statut']); ?>
                                </span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['date_statuts'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Aucune facture enregistrée dans ce bordereau.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


        <?php include '../includes/footer.php'; ?>

    </div>

</div>