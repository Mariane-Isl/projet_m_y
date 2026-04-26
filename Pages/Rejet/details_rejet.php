<?php
session_start();

// 1. On récupère l'ID uniquement en session
$id = $_SESSION['details_rejet_id'] ?? 0;
// 2. Si l'ID est 0, c'est que l'utilisateur force l'accès, on le renvoie à la liste
if ($id <= 0) {
    header("Location: Liste_rejet.php");
    exit();
}

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Rejet.php';
require_once __DIR__ . '/../../classes/historique_rejet.php';
require_once __DIR__ . '/../../classes/historique_facture.php';
require_once '../../Controllers/Rejet/RejetController.php';

$database = new Database();
$db       = $database->getConnection();

// ── 1. GESTION DE L'ID ET NETTOYAGE DE L'URL (PRG Pattern) ───────────────
// Si on arrive avec un ID dans l'URL ou en POST, on le met en session et on nettoie l'URL
if (isset($_GET['id']) || isset($_POST['rejet_id'])) {
    $incoming_id = intval($_GET['id'] ?? $_POST['rejet_id']);
    if ($incoming_id > 0) {
        $_SESSION['details_rejet_id'] = $incoming_id;
        // On redirige vers la même page SANS paramètre dans l'URL
        header("Location: details_rejet.php");
        exit();
    }
}

// On récupère l'ID depuis la session
$id = $_SESSION['details_rejet_id'] ?? 0;

// Sécurité : si on arrive ici sans ID en session, on retourne à la liste
if ($id <= 0) {
    header("Location: Liste_rejet.php");
    exit();
}

// ── 2. CHARGEMENT DES DONNÉES DEPUIS LE MODÈLE (STRICT MVC) ───────────────
$data = Rejet::getDetailsById($db, $id);

if (!$data || !$data['infos']) {
    $_SESSION['flash_message'] = "Le rejet est introuvable.";
    $_SESSION['flash_type']    = "danger";
    header("Location: Liste_rejet.php");
    exit();
}

$rejet    = $data['infos'];
$factures = $data['factures'];

// On utilise le créateur récupéré par le SQL
$nomCreateur = trim(($rejet['createur_nom'] ?? '') . ' ' . ($rejet['createur_prenom'] ?? ''));
$affichageCreateur = !empty($nomCreateur) ? $nomCreateur : 'Utilisateur inconnu';

// Codification (Directement depuis la base de données)
$codification = $rejet['num_rejet'] ?? 'Inconnu';

// Liste des factures pour la modal d'ajout
$availableFactures = Rejet::getAvailableFactures($db, $id);

// 1. Harmonisation du nom de la variable (on utilise tout en minuscules pour plus de sécurité)
$statutcode  = $rejet['statut_code']  ?? '';
$statutlabel = $rejet['statut_label'] ?? 'Inconnu';

// 2. On prépare le badge
$badgeClass  = match ($statutcode) {
    'CREE'  => 'badge-ouvert',
    'RECUP' => 'badge-clos',
    default => 'badge-default'
};

$page_title = "Détails du Rejet #" . $codification;
$selfUrl = "../../Controllers/Rejet/RejetController.php";
?>
<?php include __DIR__ . '/../Includes/header.php'; ?>
<?php include __DIR__ . '/../Includes/sidebar.php'; ?>

<style>
    body {
        background-color: #0b0e11 !important;
    }

    .main-content {
        background-color: #0b0e11 !important;
        min-height: 100vh;
        flex: 1;
        color: #fff;
    }

    .card-dark {
        background-color: #15191d;
        border: 1px solid #24292d;
        border-radius: 12px;
    }

    .info-label {
        color: #5d666d;
        font-size: 0.70rem;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-value {
        color: #e8eaf0;
        font-size: 0.86rem;
        background: #0f1215;
        border: 1px solid #24292d;
        border-radius: 6px;
        padding: 7px 11px;
        margin-bottom: 14px;
        min-height: 34px;
    }

    .badge-statut {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        font-size: 0.84rem;
        font-weight: 700;
        margin-bottom: 18px;
        letter-spacing: 0.4px;
    }

    .badge-ouvert {
        background: #e67e22;
        color: #fff;
    }

    .badge-clos {
        background: #00c875;
        color: #fff;
    }

    .badge-default {
        background: #444c56;
        color: #fff;
    }

    .table-rejet td,
    .table-rejet th {
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid #1f2327 !important;
        padding: 11px 14px !important;
        color: #e8eaf0 !important;
        font-size: 0.84rem;
        vertical-align: middle;
    }

    .table-rejet thead td {
        color: #5d666d !important;
        font-size: 0.70rem !important;
        text-transform: uppercase;
        font-weight: 600;
    }

    .table-rejet tbody tr:hover {
        background: rgba(0, 195, 255, .03) !important;
    }

    .badge-facture {
        background: rgba(0, 195, 255, .1);
        color: #00c3ff;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 0.77rem;
        font-weight: 700;
        border: 1px solid rgba(0, 195, 255, .2);
    }

    .btn-cyan {
        background: #00c3ff;
        color: #000;
        font-weight: 700;
        border: none;
        border-radius: 8px;
        padding: 9px 22px;
        font-size: 0.82rem;
        cursor: pointer;
        transition: opacity .2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-cyan:hover {
        opacity: .85;
    }

    .btn-green {
        background: #00c875;
        color: #fff;
        font-weight: 700;
        border: none;
        border-radius: 8px;
        padding: 9px 22px;
        font-size: 0.82rem;
        cursor: pointer;
        transition: opacity .2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-green:hover {
        opacity: .85;
    }

    .btn-grey {
        background: transparent;
        color: #8b949e;
        border: 1px solid #444c56;
        border-radius: 8px;
        padding: 9px 22px;
        font-size: 0.82rem;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-grey:hover {
        border-color: #8b949e;
        color: #fff;
    }

    .btn-red-outline {
        background: transparent;
        color: #e74c3c;
        border: 1px solid #e74c3c;
        border-radius: 8px;
        padding: 9px 22px;
        font-size: 0.82rem;
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: 100%;
        justify-content: center;
        margin-top: 10px;
    }

    .btn-red-outline:hover {
        background: #e74c3c;
        color: #fff;
    }

    .btn-remove {
        background: transparent;
        border: none;
        color: #e74c3c;
        cursor: pointer;
        padding: 5px 8px;
        border-radius: 5px;
        transition: background .2s;
        line-height: 1;
    }

    .btn-remove:hover {
        background: rgba(231, 76, 60, .12);
    }

    .btn-add-facture {
        background: #00c875;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: opacity .2s;
    }

    .btn-add-facture:hover {
        opacity: .85;
    }

    textarea.motif-area {
        background: #0f1215 !important;
        border: 1px solid #24292d !important;
        color: #e8eaf0 !important;
        border-radius: 6px;
        font-size: 0.84rem;
        resize: vertical;
        width: 100%;
        padding: 9px 11px;
        outline: none;
        transition: border-color .2s;
    }

    textarea.motif-area:focus {
        border-color: #00c3ff !important;
        box-shadow: 0 0 0 2px rgba(0, 195, 255, .12);
    }

    .section-divider {
        border: none;
        border-top: 1px solid #1f2327;
        margin: 16px 0;
    }

    .bread {
        font-size: .77rem;
        color: #5d666d;
    }

    .bread a {
        color: #5d666d;
        text-decoration: none;
    }

    .bread a:hover {
        color: #fff;
    }

    /* Modal dark */
    .modal-content {
        background: #15191d !important;
        border: 1px solid #24292d !important;
        border-radius: 12px !important;
        color: #e8eaf0 !important;
    }

    .modal-header {
        border-bottom: 1px solid #24292d !important;
    }

    .modal-footer {
        border-top: 1px solid #24292d !important;
    }

    .table-modal td,
    .table-modal th {
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid #1f2327 !important;
        padding: 10px 12px !important;
        color: #e8eaf0 !important;
        font-size: 0.82rem;
        vertical-align: middle;
    }

    .table-modal thead th {
        color: #5d666d !important;
        font-size: 0.70rem !important;
        text-transform: uppercase;
        font-weight: 600;
    }

    .badge-affected {
        background: rgba(0, 200, 117, .12);
        color: #00c875;
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 0.72rem;
        font-weight: 600;
        border: 1px solid rgba(0, 200, 117, .2);
    }
</style>

<div class="main-content">
    <?php include __DIR__ . '/../Includes/topbar.php'; ?>

    <div class="container-fluid p-4">

        <!-- ── Flash messages ──────────────────────────────────────────── -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: '<?= $_SESSION['flash_type'] === 'danger' ? 'error' : ($_SESSION['flash_type'] === 'warning' ? 'warning' : 'success') ?>',
                        title: '<?= $_SESSION['flash_type'] === 'success' ? 'Succès !' : 'Attention' ?>',
                        text: '<?= addslashes($_SESSION['flash_message']) ?>',
                        confirmButtonColor: '#00c3ff',
                        confirmButtonText: 'OK'
                    });
                });
            </script>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        endif; ?>

        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div style="display:flex;align-items:center;gap:10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#e74c3c">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                </svg>
                <h4 style="font-weight:700;margin:0;font-size:1.1rem;">
                    Détails du Rejet
                    <span style="color:#5d666d;font-weight:500;"> #<?= htmlspecialchars($codification) ?></span>
                </h4>
                <a href="../../Controllers/LOCAL_API/Rejets/export_rejet_pdf.php?id=<?= $id ?>"
                    target="_blank"
                    style="background:#24292d;border:none;color:#8b949e;border-radius:6px;padding:5px 12px;font-size:0.75rem;cursor:pointer;display:flex;align-items:center;gap:5px;margin-left:6px;text-decoration:none;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z" />
                    </svg>
                    Imprimer la Note
                </a>
            </div>
            <div class="bread">
                <a href="../dashboard.php">Accueil</a>
                <span style="margin:0 5px;">/</span>
                <!-- On pointe directement vers la page de la liste située dans le même dossier -->
                <a href="Liste_rejet.php" style="color:var(--text-muted);text-decoration:none;">Liste des Rejets</a>
                <span style="margin:0 5px;">/</span>
                <span style="color:#fff;">Détails</span>
            </div>
        </div>

        <div class="row g-4">

            <!-- ══ LEFT COLUMN ═════════════════════════════════════════════ -->
            <div class="col-md-4">
                <div class="card-dark p-4" style="display:flex;flex-direction:column;gap:0;">

                    <div style="display:flex;align-items:center;gap:7px;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #1f2327;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="#00c3ff">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                        </svg>
                        <span style="font-weight:700;font-size:0.84rem;">Informations Générales</span>
                    </div>

                    <!-- Statut badge -->
                    <div class="badge-statut <?= $badgeClass ?>">
                        <?php if ($statutcode === 'RECUP'): ?>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                            </svg>
                        <?php endif; ?>
                        <?= htmlspecialchars($statutlabel ?? '') ?>
                    </div>

                    <!-- Info fields -->
                    <div class="info-label">Date du rejet</div>
                    <div class="info-value" style="color:#e74c3c;font-weight:700;">
                        <?= !empty($rejet['date_rejet']) ? date('d/m/Y', strtotime($rejet['date_rejet'])) : '—' ?>
                    </div>

                    <div class="info-label">Généré par</div>
                    <div class="info-value" style="display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-user-circle text-secondary" style="font-size: 0.9rem;"></i>
                        <!-- On affiche directement le nom de la session -->
                        <span style="color: #ffffff; font-weight: 500;">
                            <?= htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur') ?>
                        </span>
                    </div>

                    <div class="info-label">Fournisseur</div>
                    <div class="info-value"><?= htmlspecialchars($rejet['Nom_Fournisseur'] ?? '—') ?></div>

                    <div class="info-label">Contrat</div>
                    <div class="info-value" style="color:#00c3ff;"><?= htmlspecialchars($rejet['num_Contrat'] ?? '—') ?></div>

                    <div class="info-label">Structure</div>
                    <div class="info-value"><?= htmlspecialchars($rejet['structure_nom'] ?? '—') ?></div>

                    <div class="info-label">Type Rejet</div>
                    <div class="info-value" style="color:#8b949e;">REJET FOURNISSEURS ETRANGER</div>

                    <hr class="section-divider">

                    <!-- Motif form — posts to self -->
                    <form method="POST" action="<?= $selfUrl ?>" id="formSave">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="action" value="save_cause">

                        <div class="info-label" style="margin-bottom:6px;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" style="margin-right:3px;">
                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                            </svg>
                            Motif du rejet
                        </div>
                        
                        <!-- Le champ est en readonly si le statut est RECUP -->
                        <textarea name="cause" class="motif-area" rows="4"
                            <?= $statutcode === 'RECUP' ? 'readonly style="opacity: 0.6; cursor: not-allowed;"' : '' ?>
                            placeholder="Saisir le motif du rejet..."><?= htmlspecialchars($rejet['cause'] ?? '') ?></textarea>

                        <hr class="section-divider">

                        <!-- Récupérer button -->
                        <?php if ($statutcode === 'CREE'): ?>
                            <button type="button" onclick="confirmerRecuperation()" class="btn-green"
                                style="width:100%;justify-content:center;margin-bottom:10px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z" />
                                </svg>
                                ↩ Récupérer le Rejet
                            </button>
                        <?php elseif ($statutcode === 'RECUP'): ?>
                            <div style="width:100%;text-align:center;padding:9px;margin-bottom:10px;background:rgba(0,200,117,.08);border:1px solid rgba(0,200,117,.2);border-radius:8px;font-size:0.82rem;color:#00c875;font-weight:600;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" style="margin-right:5px;">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                </svg>
                                Rejet déjà récupéré
                            </div>
                        <?php endif; ?>

                        <!-- Annuler / Enregistrer -->
                        <div style="display:flex;gap:8px;">
                            <button type="button" onclick="window.location.href='Liste_rejet.php'" class="btn-grey" style="flex:1; justify-content:center;">
                                ← Retour
                            </button>
                            
                            <!-- Le bouton Enregistrer est masqué si le statut est RECUP -->
                            <?php if ($statutcode !== 'RECUP'): ?>
                                <button type="button" onclick="confirmerEnregistrement()" class="btn-cyan" style="flex:2;justify-content:center;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
                                    </svg>
                                    Enregistrer
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Delete form -->
                <!-- Le bouton Supprimer est masqué si le statut est RECUP -->
                <?php if ($statutcode !== 'RECUP'): ?>
                    <form method="POST" action="<?= $selfUrl ?>" id="formDelete">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="action" value="delete_rejet">
                        <button type="button" onclick="confirmerSuppression()" class="btn-red-outline">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                            </svg>
                            Supprimer définitivement
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- ══ RIGHT COLUMN ═════════════════════════════════════════════ -->
            <div class="col-md-8">
                <div class="card-dark" style="height:100%;">

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid #1f2327;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="#00c3ff">
                                <path d="M3 9h2V7H3v2zm0 4h2v-2H3v2zm0 4h2v-2H3v2zm4-8h11V7H7v2zm0 4h11v-2H7v2zm0 4h11v-2H7v2z" />
                            </svg>
                            <span style="font-weight:700;font-size:0.86rem;">Factures Associées</span>
                            <span style="background:#24292d;color:#8b949e;font-size:0.70rem;padding:2px 8px;border-radius:10px;">
                                <?= count($factures) ?>
                            </span>
                        </div>
                        <?php if ($statutcode !== 'RECUP'): ?>
                            <button class="btn-add-facture" data-bs-toggle="modal" data-bs-target="#modalAddFactures">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                </svg>
                                Ajouter des factures
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-rejet mb-0">
                            <thead>
                                <tr>
                                    <td>ID</td>
                                    <td>N° Facture</td>
                                    <td>Date</td>
                                    <td style="text-align:right;">Montant</td>
                                    <td style="text-align:center;">Action</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($factures)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;padding:40px !important;color:#5d666d;">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="rgba(255,255,255,0.08)" style="display:block;margin:0 auto 10px;">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                                            </svg>
                                            Aucune facture associée à ce rejet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($factures as $f): ?>
                                        <tr>
                                            <td style="color:#5d666d;"><?= htmlspecialchars($f['id']) ?></td>
                                            <td><span class="badge-facture"><?= htmlspecialchars($f['Num_facture']) ?></span></td>
                                            <td style="color:#8b949e;font-size:0.80rem;">
                                                <?= !empty($f['date_facture']) ? date('d/m/Y', strtotime($f['date_facture'])) : '—' ?>
                                            </td>
                                            <td style="text-align:right;font-weight:700;">
                                                <?= number_format(floatval($f['Montant']), 2, '.', ' ') ?>
                                                <small style="color:#5d666d;font-weight:400;margin-left:3px;">
                                                    <?= htmlspecialchars($f['monnaie_code']) ?>
                                                </small>
                                            </td>
                                            <td style="text-align:center;">
                                                <?php if ($statutcode !== 'RECUP'): ?>
                                                    <form method="POST" action="<?= $selfUrl ?>" style="display:inline;">
                                                        <input type="hidden" name="id" value="<?= $id ?>">
                                                        <input type="hidden" name="action" value="remove_facture">
                                                        <input type="hidden" name="facture_id" value="<?= $f['id'] ?>">
                                                        <button type="button" class="btn-remove"
                                                            onclick="confirmerRetraitFacture(this)"
                                                            title="Retirer cette facture">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                                                <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <i class="fas fa-lock text-muted" title="Rejet clôturé"></i>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /row -->
    </div><!-- /container -->

    <!-- ══ MODAL: Ajouter des factures ════════════════════════════════════ -->
    <div class="modal fade" id="modalAddFactures" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="padding:16px 20px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="#00c875">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                        </svg>
                        <h5 class="modal-title" style="font-size:0.92rem;font-weight:700;margin:0;">
                            Sélectionner des factures à ajouter
                        </h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST" action="<?= $selfUrl ?>" id="formAddFactures">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="action" value="add_factures">
                    <div class="modal-body" style="padding:16px 20px;">
                        <?php if (empty($availableFactures)): ?>
                            <p style="text-align:center;color:#5d666d;padding:20px 0;">
                                Toutes les factures disponibles sont déjà associées à ce rejet.
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modal mb-0" id="tableAvailableFactures">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;"></th>
                                            <th>N° Facture</th>
                                            <th>Date</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($availableFactures as $af): ?>
                                            <tr style="cursor:pointer;" onclick="toggleRow(this)">
                                                <td style="text-align:center;">
                                                    <input type="checkbox" name="facture_ids[]"
                                                        value="<?= $af['id'] ?>"
                                                        style="accent-color:#00c875;width:15px;height:15px;"
                                                        onclick="event.stopPropagation();">
                                                </td>
                                                <td><span class="badge-facture"><?= htmlspecialchars($af['Num_facture']) ?></span></td>
                                                <td style="color:#8b949e;font-size:0.80rem;">
                                                    <?= !empty($af['date_facture']) ? date('d/m/Y', strtotime($af['date_facture'])) : '—' ?>
                                                </td>
                                                <td style="font-weight:600;">
                                                    <?= number_format(floatval($af['Montant']), 2, '.', ' ') ?>
                                                    <small style="color:#5d666d;"> <?= htmlspecialchars($af['monnaie_code']) ?></small>
                                                </td>
                                                <td><span class="badge-affected"><?= htmlspecialchars($af['statut_label'] ?? 'N/A') ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer" style="padding:12px 20px;justify-content:flex-end;gap:10px;">
                        <button type="button" class="btn-grey" data-bs-dismiss="modal">Annuler</button>
                        <?php if (!empty($availableFactures)): ?>
                            <button type="submit" class="btn-green">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                </svg>
                                + Ajouter la sélection
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
      <?php include __DIR__ . '/../Includes/footer.php'; ?>
    </div>

    <form id="formRecuperer" method="POST" action="<?= $selfUrl ?>" style="display:none;">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="action" value="recuperer">
    </form>
</div><!-- /main-content -->

<script>
    // DataTable for modal
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $.fn.DataTable !== 'undefined' && document.getElementById('tableAvailableFactures')) {
            $.fn.dataTable.ext.errMode = 'none';
            $('#tableAvailableFactures').DataTable({
                language: {
                    search: 'Rechercher :',
                    zeroRecords: 'Aucun résultat'
                },
                pageLength: 5,
                lengthMenu: [5, 10, 25],
                order: [
                    [2, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [0, 4]
                }]
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Appel AJAX pour récupérer l'utilisateur connecté
        fetch('../../Controllers/LOCAL_API/User/get_current_user.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // On écrit le nom de Boucham Youcef (ou celui qui est connecté)
                    document.getElementById('display_creator').innerText = data.user_name;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('display_creator').innerText = 'Erreur chargement';
            });
    });

    function toggleRow(tr) {
        const cb = tr.querySelector('input[type="checkbox"]');
        if (cb) {
            cb.checked = !cb.checked;
            tr.style.background = cb.checked ? 'rgba(0,200,117,0.06)' : '';
        }
    }

    function confirmerSuppression() {
        Swal.fire({
            title: 'Supprimer définitivement ?',
            html: 'Le rejet <strong>#<?= addslashes(htmlspecialchars($codification)) ?></strong> et tout son historique seront supprimés.<br><br><span style="color:#e74c3c;font-size:0.83rem;">Cette action est irréversible.</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#444c56',
            confirmButtonText: '🗑️ Supprimer définitivement',
            cancelButtonText: 'Annuler'
        }).then(r => {
            if (r.isConfirmed) document.getElementById('formDelete').submit();
        });
    }

    function confirmerEnregistrement() {
        // 1. On vérifie si le motif n'est pas vide
        const cause = document.querySelector('textarea[name="cause"]').value.trim();

        if (cause === "") {
            Swal.fire({
                icon: 'warning',
                title: 'Motif requis',
                text: 'Veuillez saisir un motif pour le rejet avant d\'enregistrer.',
                confirmButtonColor: '#00c3ff'
            });
            return;
        }

        // 2. Si c'est bon, on demande confirmation
        Swal.fire({
            title: 'Enregistrer les modifications ?',
            text: "Le motif du rejet sera mis à jour en base de données.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00c3ff',
            cancelButtonColor: '#444c56',
            confirmButtonText: 'Oui, enregistrer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                // On envoie le formulaire qui a l'ID "formSave"
                document.getElementById('formSave').submit();
            }
        });
    }

    function confirmerRecuperation() {
        Swal.fire({
            title: 'Récupérer ce rejet ?',
            html: 'Le rejet <strong>#<?= addslashes(htmlspecialchars($codification)) ?></strong> sera marqué comme <strong>Récupéré</strong>.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00c875',
            cancelButtonColor: '#444c56',
            confirmButtonText: '↩ Confirmer la récupération',
            cancelButtonText: 'Annuler'
        }).then(r => {
            if (r.isConfirmed) document.getElementById('formRecuperer').submit();
        });
    }

    function confirmerRetraitFacture(btn) {
        Swal.fire({
            title: 'Retirer cette facture ?',
            text: 'La facture sera dissociée de ce rejet.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#444c56',
            confirmButtonText: 'Retirer',
            cancelButtonText: 'Annuler'
        }).then(r => {
            if (r.isConfirmed) btn.closest('form').submit();
        });
    }
</script>