<?php


session_start();

require_once '../../../classes/Database.php';
require_once '../../../classes/Bordereau.php';


// ── Récupération ID via POST ou SESSION ───────────────────────────────────
if (isset($_POST['bordereau_id']) && intval($_POST['bordereau_id']) > 0) {
    $_SESSION['accuser_bordereau_id'] = intval($_POST['bordereau_id']);
}

$bordereau_id = isset($_SESSION['accuser_bordereau_id'])
                ? intval($_SESSION['accuser_bordereau_id'])
                : 0;

if ($bordereau_id <= 0) {
    header("Location: Reception_bordereaux.php");
    exit();
}

// ── Connexion BDD ─────────────────────────────────────────────────────────
$database = new Database();
$db       = $database->getConnection();

// ── Vérifier déjà accusé ─────────────────────────────────────────────────
if (Bordereau::hasStatut($db, $bordereau_id, 'NON_CONTROLE')) {
    $_SESSION['flash_message'] = "Ce bordereau a déjà été accusé de réception.";
    $_SESSION['flash_type']    = "warning";
    header("Location: Reception_bordereaux.php");
    exit();
}

// ── Récupérer les détails complets ────────────────────────────────────────
$bordereauObj = new Bordereau($db);
$data = $bordereauObj->getFullDetails($bordereau_id);

if (!$data || !$data['header']) {
    $_SESSION['flash_message'] = "Bordereau introuvable.";
    $_SESSION['flash_type']    = "danger";
    header("Location: Reception_bordereaux.php");
    exit();
}

$b        = $data['header'];
$factures = $data['factures'];

// ── Construction de la référence complète ─────────────────────────────────
$annee = date('Y', strtotime($b['date_bordereau'] ?? 'now'));
$reg   = !empty($b['region_code']) ? $b['region_code'] : 'SG';
$num   = !empty($b['num_bordereau']) ? $b['num_bordereau'] : $bordereau_id;
$reference_complete = $reg . '/' . $num . '/' . $annee;

$page_title = "Accusé Réception — " . $reference_complete;
?>
<?php include '../Includes/header.php'; ?>
<?php include '../Includes/sidebar.php'; ?>

<style>
.card-dark {
    background-color: var(--bg-card, #15191d) !important;
    border: 1px solid var(--card-border, #24292d) !important;
    border-radius: 12px !important;
    margin-bottom: 25px;
}

.info-group {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.info-label {
    color: var(--text-muted, #5d666d);
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: 600;
    width: 220px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.info-value {
    color: var(--text-primary, #ffffff);
    font-size: 0.92rem;
    font-weight: 500;
}

.table-custom td,
.table-custom th {
    background-color: transparent !important;
    border: none !important;
    border-bottom: 1px solid var(--card-border, #1f2327) !important;
    padding: 14px 16px !important;
    color: var(--text-primary, #ffffff) !important;
    vertical-align: middle;
}

.table-custom thead th {
    color: var(--text-muted, #5d666d) !important;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.badge-code {
    background-color: rgba(0, 191, 255, 0.1) !important;
    color: var(--accent-blue, #00bfff) !important;
    padding: 4px 12px !important;
    border-radius: 20px !important;
    font-size: 0.75rem;
    font-weight: bold;
    border: 1px solid rgba(0, 191, 255, 0.2);
}
</style>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="content-area">

        <!-- BREADCRUMB + TITRE -->
        <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
            <h4 style="color:var(--text-primary);font-weight:600;margin:0;">
                Accusé de Réception — Bordereau N°
                <span style="color:var(--accent-blue);"><?= htmlspecialchars($reference_complete) ?></span>
            </h4>
            <div style="font-size:0.8rem;color:var(--text-muted);">
                <a href="../dashboard.php" style="color:var(--text-muted);text-decoration:none;">Accueil</a>
                <span style="margin:0 6px;">/</span>
                <a href="Reception_bordereaux.php" style="color:var(--text-muted);text-decoration:none;">Réception
                    Bordereaux</a>
                <span style="margin:0 6px;">/</span>
                <span style="color:var(--text-primary);"><?= htmlspecialchars($reference_complete) ?></span>
            </div>
        </div>

        <!-- CARTE DÉTAILS BORDEREAU -->
        <div class="card card-dark">
            <div class="card-body p-4">
                <div class="row">

                    <!-- Colonne gauche -->
                    <div class="col-md-6">
                        <div class="info-group">
                            <div class="info-label">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M3 9h2V7H3v2zm0 4h2v-2H3v2zm0 4h2v-2H3v2zm4-8h11V7H7v2zm0 4h11v-2H7v2zm0 4h11v-2H7v2z" />
                                </svg>
                                N° Bordereau :
                            </div>
                            <div class="info-value" style="color:var(--text-muted);">
                                <?= htmlspecialchars($reference_complete) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M19 4h-1V2h-2v2H8V2H6v2H5C3.9 4 3 4.9 3 6v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z" />
                                </svg>
                                Date Bordereau :
                            </div>
                            <div class="info-value">
                                <?= !empty($b['date_bordereau']) ? date('d/m/Y', strtotime($b['date_bordereau'])) : '—' ?>
                            </div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                                </svg>
                                Contrat N° :
                            </div>
                            <div class="info-value" style="color:var(--accent-blue);">
                                <?= htmlspecialchars($b['num_Contrat'] ?? '—') ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 7V3H2v18h20V7H12z" />
                                </svg>
                                Fournisseur :
                            </div>
                            <div class="info-value"><?= htmlspecialchars($b['Nom_Fournisseur'] ?? '—') ?></div>
                        </div>
                    </div>

                    <!-- Colonne droite -->
                    <div class="col-md-6" style="border-left:1px solid var(--card-border);">
                        <div class="info-group ps-4">
                            <div class="info-label">Structure :</div>
                            <div class="info-value"><?= htmlspecialchars($b['structure_nom'] ?? 'SIEGE-DP') ?></div>
                        </div>
                        <div class="info-group ps-4">
                            <div class="info-label">Créé par :</div>
                            <div class="info-value">
                                <?= htmlspecialchars(trim(($b['user_nom'] ?? '') . ' ' . ($b['user_prenom'] ?? ''))) ?: '—' ?>
                            </div>
                        </div>
                        <div class="info-group ps-4">
                            <div class="info-label">Statut actuel :</div>
                            <div class="info-value">
                                <?php
                            $code_etat = $b['code_etat'] ?? 'TRANSMIS';
                            $badgeBg = match($code_etat) {
                                'NON_CONTROLE', 'ARRIVE' => '#e74c3c',
                                'RECEPTION'              => '#27ae60',
                                'TRANSMIS'               => '#2980b9',
                                default                  => '#7f8c8d'
                            };
                            ?>
                                <span
                                    style="background:<?= $badgeBg ?>;color:#fff;padding:3px 12px;border-radius:4px;font-size:0.77rem;font-weight:700;">
                                    <?= htmlspecialchars($b['label_etat'] ?? $code_etat) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Bouton Accuser -->
                        <div class="text-end mt-4 pe-2">
                            <button type="button" id="btnAccuser"
                                style="background:var(--accent-blue);color:#fff;border:none;padding:9px 24px;border-radius:8px;font-weight:700;font-size:0.88rem;cursor:pointer;transition:opacity 0.2s;"
                                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                ✅ Accuser Réception
                            </button>
                            <a href="Reception_bordereaux.php" class="btn btn-secondary ms-2"
                                style="font-size:0.85rem;">
                                Annuler
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Formulaire caché POST vers BordController -->
        <form id="formAccuser" action="../../Controllers/Bordereux/BordController.php" method="POST"
            style="display:none;">
            <input type="hidden" name="action" value="accuser_bordereau">
            <input type="hidden" name="bordereau_id" value="<?= htmlspecialchars($bordereau_id) ?>">
            <input type="hidden" name="date_accuse" id="hidden_date_accuse" value="<?= date('Y-m-d') ?>">
        </form>

        <!-- TABLEAU DES FACTURES -->
        <div class="card card-dark">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>N° Facture</th>
                                <th>Date Facture</th>
                                <th>Montant</th>
                                <th>Monnaie</th>
                                <th>Structure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($factures)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">
                                    Aucune facture pour ce bordereau.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($factures as $f): ?>
                            <tr>
                                <td>
                                    <span class="badge-code"><?= htmlspecialchars($f['Num_facture']) ?></span>
                                </td>
                                <td style="color:var(--text-muted);">
                                    <?= !empty($b['date_bordereau']) ? date('d/m/Y', strtotime($b['date_bordereau'])) : '—' ?>
                                </td>
                                <td style="font-weight:600;">
                                    <?= number_format(floatval($f['Montant']), 2, '.', ' ') ?>
                                    <span
                                        style="color:var(--text-muted);font-size:0.78rem;"><?= htmlspecialchars($f['monnaie_code'] ?? '') ?></span>
                                </td>
                                <td><?= htmlspecialchars($f['monnaie_label'] ?? $f['monnaie_code'] ?? '—') ?></td>
                                <td style="color:var(--text-muted);">
                                    <?= htmlspecialchars($b['structure_nom'] ?? 'SIEGE-DP') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /content-area -->

    <?php include '../Includes/footer.php'; ?>

    <script src="../../dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // ── Flash messages après redirect ────────────────────────────────────────
    <?php if (isset($_SESSION['flash_message'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?= $_SESSION['flash_type'] === 'danger' ? 'error' : $_SESSION['flash_type'] ?>',
            title: '<?= $_SESSION['flash_type'] === 'success' ? 'Succès !' : 'Attention' ?>',
            text: '<?= addslashes($_SESSION['flash_message']) ?>',
            timer: 3500,
            showConfirmButton: false
        });
    });
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    // ── Bouton Accuser Réception ──────────────────────────────────────────────
    document.getElementById('btnAccuser').addEventListener('click', function() {
        Swal.fire({
            title: "Confirmer l'accusé de réception ?",
            html: 'Le bordereau <strong><?= addslashes(htmlspecialchars($reference_complete)) ?></strong> sera marqué comme <strong>Accusé</strong>.<br><br>' +
                '<div style="margin-top:8px;">' +
                '<label style="font-size:0.85rem;color:#aaa;display:block;margin-bottom:4px;">Date de l\'accusé</label>' +
                '<div style="padding:7px 12px;border-radius:6px;border:1px solid #444;background:#2a3145;color:#e0e6f0;font-size:0.88rem;text-align:center;font-weight:600;">' +
                '<?= date('d/m/Y') ?>' +
                '</div></div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b9eff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '✅ Oui, accuser réception',
            cancelButtonText: 'Annuler'
        }).then(function(result) {
            if (result.isConfirmed) {
                document.getElementById('hidden_date_accuse').value = '<?= date('Y-m-d') ?>';
                document.getElementById('btnAccuser').disabled = true;
                document.getElementById('btnAccuser').innerHTML =
                    '<span class="spinner-border spinner-border-sm" style="width:12px;height:12px;"></span> Enregistrement...';
                document.getElementById('formAccuser').submit();
            }
        });
    });
    </script>

</div><!-- /main-content -->