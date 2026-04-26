<?php
require_once '../../Controllers/Facture/FactureController.php';

if (!$facture) {
    header("Location: Recherche_Facture.php");
    exit();
}

$page_title = "Dossier Facture N° " . $facture['Num_facture'];
?>
<?php include '../Includes/header.php'; ?>
<?php include '../Includes/sidebar.php'; ?>

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
        margin-bottom: 20px;
        overflow: hidden; 
    }

    /* ── Status banner ── */
    .status-banner {
        border-radius: 10px;
        padding: 18px 24px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 800;
        font-size: 1.05rem;
        letter-spacing: 0.3px;
        position: relative;
        overflow: hidden;
    }

    .status-banner.en-cours {
        background: #e6a817;
        color: #000;
    }

    .status-banner.paye {
        background: #00c875;
        color: #fff;
    }

    .status-banner.rejete {
        background: #e74c3c;
        color: #fff;
    }

    .status-banner.non-ctrl {
        background: #8b949e;
        color: #fff;
    }

    .status-banner.affected {
        background: #3b9eff;
        color: #fff;
    }

    .status-banner .banner-icon {
        position: absolute;
        right: 20px;
        font-size: 3.5rem;
        opacity: .18;
        line-height: 1;
    }

    /* ── Info fields ── */
    .info-label {
        color: #5d666d;
        font-size: 0.70rem;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.4px;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .info-value {
        color: #e8eaf0;
        font-size: 0.88rem;
        font-weight: 500;
        margin-bottom: 16px;
    }

    .info-value.accent {
        color: #00c3ff;
    }

    .info-value.danger {
        color: #e74c3c;
    }

    .info-value.green {
        color: #00c875;
    }

    /* Amount box */
    .amount-box {
        text-align: right;
        padding-left: 24px;
        border-left: 1px solid #24292d;
    }

    .amount-label {
        color: #5d666d;
        font-size: 0.70rem;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .amount-value {
        color: #00c875;
        font-size: 2rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .amount-curr {
        color: #8b949e;
        font-size: 0.95rem;
        font-weight: 400;
    }

    /* Tables shared */
    .tbl-dark td,
    .tbl-dark th {
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid #1f2327 !important;
        padding: 11px 14px !important;
        color: #e8eaf0 !important;
        font-size: 0.83rem;
        vertical-align: middle;
    }

    .tbl-dark thead th {
        color: #5d666d !important;
        font-size: 0.70rem !important;
        text-transform: uppercase;
        font-weight: 600;
        border-bottom: 2px solid #24292d !important;
    }

    .tbl-dark tbody tr:hover {
        background: rgba(0, 195, 255, .03) !important;
    }

    /* Statut badges */
    .badge-ov-trait {
        background: rgba(59, 158, 255, .15);
        color: #3b9eff;
        border: 1px solid rgba(59, 158, 255, .25);
        padding: 3px 10px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-ov-atf {
        background: rgba(230, 126, 34, .15);
        color: #e67e22;
        border: 1px solid rgba(230, 126, 34, .25);
        padding: 3px 10px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-ov-annul {
        background: rgba(231, 76, 60, .15);
        color: #e74c3c;
        border: 1px solid rgba(231, 76, 60, .25);
        padding: 3px 10px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-f-recu {
        background: rgba(230, 126, 34, .15);
        color: #e67e22;
        border: 1px solid rgba(230, 126, 34, .25);
        padding: 3px 10px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-f-paye {
        background: rgba(0, 200, 117, .15);
        color: #00c875;
        border: 1px solid rgba(0, 200, 117, .25);
        padding: 3px 10px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-f-rejete {
        background: rgba(231, 76, 60, .15);
        color: #e74c3c;
        border: 1px solid rgba(231, 76, 60, .25);
        padding: 3px 10px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-f-affect {
        background: rgba(59, 158, 255, .15);
        color: #3b9eff;
        border: 1px solid rgba(59, 158, 255, .25);
        padding: 3px 10px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-default-b {
        background: rgba(93, 102, 109, .15);
        color: #8b949e;
        border: 1px solid rgba(93, 102, 109, .25);
        padding: 3px 10px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .card-section-title {
        font-size: .78rem;
        font-weight: 700;
        color: #8b949e;
        text-transform: uppercase;
        letter-spacing: .5px;
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 13px 20px;
        border-bottom: 1px solid #1f2327;
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

    .btn-back {
        background: transparent;
        color: #8b949e;
        border: 1px solid #24292d;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 0.78rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        transition: all .2s;
    }

    .btn-back:hover {
        border-color: #8b949e;
        color: #fff;
    }
</style>

<?php
// Determine banner class and label from statut_actuel
$sl_lower = strtolower($statut_actuel);
$bannerClass = match (true) {
    str_contains($sl_lower, 'traitement') => 'en-cours',
    str_contains($sl_lower, 'pay')        => 'paye',
    str_contains($sl_lower, 'rejet')      => 'rejete',
    str_contains($sl_lower, 'affect')     => 'affected',
    default                               => 'non-ctrl'
};
$bannerIcon = match ($bannerClass) {
    'en-cours' => '⏳',
    'paye'     => '✅',
    'rejete'   => '✕',
    'affected' => '📋',
    default    => '📄'
};

// Badge helpers
function statutBadgeFac(string $label): string
{
    $l = strtolower($label);
    $cls = match (true) {
        str_contains($l, 'traitement') => 'badge-f-recu',
        str_contains($l, 'pay')        => 'badge-f-paye',
        str_contains($l, 'rejet')      => 'badge-f-rejete',
        str_contains($l, 'affect')     => 'badge-f-affect',
        default                        => 'badge-default-b'
    };
    return '<span class="' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

function statutBadgeOV(string $label): string
{
    $l = strtolower($label);
    $cls = match (true) {
        str_contains($l, 'traitement') => 'badge-ov-trait',
        str_contains($l, 'atf')        => 'badge-ov-atf',
        str_contains($l, 'annul')      => 'badge-ov-annul',
        default                        => 'badge-default-b'
    };
    return '<span class="' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

function statutBadgeBrd(string $code, string $label): string
{
    $cls = match ($code) {
        'RECEPTION'    => 'badge-f-paye',
        'ARRIVE'       => 'badge-ov-trait',
        'TRANSMIS'     => 'badge-default-b',
        'NON_CONTROLE' => 'badge-ov-atf',
        default        => 'badge-default-b'
    };
    return '<span class="' . $cls . '">' . htmlspecialchars($label) . '</span>';
}
?>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="container-fluid p-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="font-weight:700;margin:0;font-size:1.1rem;">
                Dossier Facture N°
                <span style="color:#00c3ff;"><?= htmlspecialchars($facture['Num_facture']) ?></span>
            </h4>
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="bread">
                    <a href="../dashboard.php">Accueil</a>
                    <span style="margin:0 5px;">/</span>
                    <a href="Recherche_Facture.php">Recherche Factures</a>
                    <span style="margin:0 5px;">/</span>
                    <span style="color:#fff;">Détails</span>
                </div>
                <a href="Recherche_Facture.php" class="btn-back">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                    </svg>
                    Retour
                </a>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="status-banner <?= $bannerClass ?>">
            <div>
                <div style="font-size:.75rem;font-weight:500;opacity:.75;margin-bottom:3px;">Etat actuel du dossier</div>
                <div><?= strtoupper(htmlspecialchars($statut_actuel)) ?></div>
            </div>
            <div class="banner-icon"><?= $bannerIcon ?></div>
        </div>

        <!-- Fiche d'identité -->
        <div class="card-dark">
            <div class="card-section-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="#00c3ff">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                </svg>
                Fiche d'identité Facture
            </div>
            <div class="row g-0" style="padding:20px;">
                <!-- Col 1 -->
                <div class="col-md-4" style="padding-right:24px;">
                    

                    <div class="info-label">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 9h2V7H3v2zm0 4h2v-2H3v2zm0 4h2v-2H3v2zm4-8h11V7H7v2zm0 4h11v-2H7v2zm0 4h11v-2H7v2z" />
                        </svg>
                        N° Bordereau
                    </div>
                    <div class="info-value accent"><?= htmlspecialchars($facture['num_bordereau'] ?? '—') ?></div>

                    <div class="info-label">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5C3.9 4 3 4.9 3 6v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z" />
                        </svg>
                        Date Facture
                    </div>
                    <div class="info-value">
                        <?= (!empty($facture['date_facture']) && $facture['date_facture'] !== '0000-00-00') ? date('d/m/Y', strtotime($facture['date_facture'])) : '—' ?>
                    </div>
                </div>

                <!-- Col 2 -->
                <div class="col-md-4" style="padding-right:24px;border-left:1px solid #1f2327;padding-left:24px;">
                    <div class="info-label">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 7V3H2v18h20V7H12z" />
                        </svg>
                        Fournisseur
                    </div>
                    <div class="info-value"><?= htmlspecialchars($facture['Nom_Fournisseur'] ?? '—') ?></div>

                    <div class="info-label">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                        </svg>
                        Contrat
                    </div>
                    <div class="info-value accent"><?= htmlspecialchars($facture['num_Contrat'] ?? '—') ?></div>

                    <div class="info-label">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.7 0 4-1.8 4-4s-1.3-4-4-4-4 1.8-4 4 1.3 4 4 4zm0 2c-4 0-6 2-6 3v1h12v-1c0-1-2-3-6-3z" />
                        </svg>
                        Structure
                    </div>
                    <div class="info-value"><?= htmlspecialchars($facture['structure_nom'] ?? '—') ?></div>
                </div>

                <!-- Col 3 -->
                <div class="col-md-4">
                    <div class="info-label" style="margin-bottom:8px;">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.7 0 4-1.8 4-4s-1.3-4-4-4-4 1.8-4 4 1.3 4 4 4zm0 2c-4 0-6 2-6 3v1h12v-1c0-1-2-3-6-3z" />
                        </svg>
                        Gestionnaire
                    </div>
                    <div class="info-value">
                        <?= htmlspecialchars(trim(($facture['gestionnaire_nom'] ?? '') . ' ' . ($facture['gestionnaire_prenom'] ?? ''))) ?: '—' ?>
                    </div>

                    <!-- Amount box -->
                    <div class="amount-box" style="border-left:none;padding-left:0;margin-top:10px;">
                        <div class="amount-label">Montant HT</div>
                        <div class="amount-value">
                            <?= number_format(floatval($facture['Montant']), 2, '.', ' ') ?>
                            <span class="amount-curr"><?= htmlspecialchars($facture['monnaie_code'] ?? '') ?></span>
                        </div>
                        <div style="margin-top:10px;">
                            <div class="amount-label">Statut Actuel</div>
                            <?= statutBadgeFac($statut_actuel) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom row: Historique + OV + Bordereau -->
        <div class="row g-3">

            <!-- Historique des statuts -->
            <div class="col-md-7">
                <div class="card-dark" style="margin-bottom:0;">
                    <div class="card-section-title">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="#8b949e">
                            <path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6a7 7 0 0 1 7-7c3.87 0 7 3.13 7 7s-3.13 7-7 7a6.98 6.98 0 0 1-4.97-2.06L6.6 18.35A9 9 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z" />
                        </svg>
                        Historique des Statuts Facture
                    </div>
                    <div style="padding:0 4px;">
                        <table class="table tbl-dark mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($historique)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center;padding:30px !important;color:#5d666d;">Aucun historique.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($historique as $h): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($h['date_statuts'])) ?></td>
                                            <td><?= statutBadgeFac($h['statut_label']) ?></td>
                                            <!-- On utilise utilisateur_nom qui vient de notre CASE WHEN SQL -->
                                            <td style="color:#8b949e;">
                                                <?= htmlspecialchars($h['utilisateur_nom'] ?? 'Système') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right column: OV + Bordereau -->
            <div class="col-md-5">

                <!-- Ordre de Virement Associé -->
                <div class="card-dark">
                    <div class="card-section-title">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="#8b949e">
                            <path d="M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1h-9a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h9zm-9-2h10V8H12v8zm4-2.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z" />
                        </svg>
                        Ordre de Virement Associé
                    </div>

                    <?php if ($ov): ?>
                        <div style="padding:14px 20px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                <div>
                                    <div class="info-label">N° OV</div>
                                    <div style="color:#00c3ff;font-weight:700;font-size:.88rem;"><?= htmlspecialchars($ov['Num_OV']) ?></div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="info-label">KTP</div>
                                    <div style="color:#8b949e;font-size:.85rem;">KTP : <?= htmlspecialchars($ov['Num_KTP']) ?></div>
                                </div>
                            </div>
                            <div style="margin-bottom:8px;">
                                <div class="info-label">Date</div>
                                <div style="color:#8b949e;font-size:.80rem;">
                                    <?= !empty($ov['Date_OV']) ? date('d/m/Y', strtotime($ov['Date_OV'])) : '—' ?>
                                </div>
                            </div>
                        </div>
                        <div style="padding:0 4px;">
                            <table class="table tbl-dark mb-0">
                                <thead>
                                    <tr>
                                        <th>Date Suivi</th>
                                        <th>Statut OV</th>
                                        <th>Utilisateur</th>
                                        <th>Durée</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($historique_ov)): ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center;color:#5d666d;padding:20px !important;">Aucun historique OV.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($historique_ov as $hov): ?>
                                            <tr>
                                                <td style="color:#8b949e;font-size:.80rem;">
                                                    <?= !empty($hov['date_status_OV']) ? date('d/m/Y', strtotime($hov['date_status_OV'])) : '—' ?>
                                                </td>
                                                <td><?= statutBadgeOV($hov['statut_label']) ?></td>
                                                <td style="color:#8b949e;font-size:.80rem;">
                                                    <?= htmlspecialchars(trim(($hov['user_nom'] ?? '') . ' ' . ($hov['user_prenom'] ?? ''))) ?: '—' ?>
                                                </td>
                                                <td style="color:#8b949e;font-size:.80rem;">
                                                    <?= $hov['duree'] !== null ? $hov['duree'] . 'j' : '—' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding:30px;text-align:center;color:#5d666d;font-size:.83rem;">
                            Aucun ordre de virement associé à cette facture.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Traçabilité Bordereau -->
                <div class="card-dark" style="margin-bottom:0;">
                    <div class="card-section-title">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="#8b949e">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                        </svg>
                        Traçabilité Bordereau
                    </div>
                    <div class="table-responsive" style="padding:0;">
                        <table class="table tbl-dark mb-0">
                            <thead>
                                <tr>
                                    <th>N° Bordereau</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Par</th>
                                    <th>Durée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($historique_bordereau)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;color:#5d666d;padding:20px !important;">Aucune traçabilité.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($historique_bordereau as $hb): ?>
                                        <tr>
                                            <td style="color:#00c3ff;font-size:.80rem;font-weight:600;">
                                                <?= htmlspecialchars($hb['num_bordereau'] ?? '—') ?>
                                            </td>
                                            <td style="color:#8b949e;font-size:.80rem;">
                                                <?= !empty($hb['date_historique']) ? date('d/m/Y', strtotime($hb['date_historique'])) : '—' ?>
                                            </td>
                                            <td><?= statutBadgeBrd($hb['statut_code'] ?? '', $hb['statut_label'] ?? '—') ?></td>
                                            <td style="color:#8b949e;font-size:.80rem;">
                                                <?= htmlspecialchars(trim(($hb['user_nom'] ?? '') . ' ' . ($hb['user_prenom'] ?? ''))) ?: '—' ?>
                                            </td>
                                            <td style="color:#8b949e;font-size:.80rem; white-space: nowrap;">
                                                <?= $hb['duree'] !== null ? $hb['duree'] . 'j' : '—' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /col-md-5 -->
        </div><!-- /row -->
            <?php include '../Includes/footer.php'; ?>
    </div><!-- /container -->
</div>

<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname);
    }
</script>