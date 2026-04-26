<?php
session_start();

// ── Protection session ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Auth/login.php");
    exit;
}

// ── Inclusions ──────────────────────────────────────────────────────────
require_once '../../classes/Database.php';
require_once '../../classes/OV.php';
require_once '../../classes/Fournisseur.php';
require_once '../../classes/NatureOv.php';
require_once '../../classes/Monnaie.php';
require_once '../../classes/region.php';

// ── Connexion BDD ───────────────────────────────────────────────────────
$database = new Database();
$db       = $database->getConnection();

// ── Données pour les filtres (via objets) ───────────────────────────────
$ovObj             = new OV($db);
$listeFournisseurs = Fournisseur::getAll($db);
$listeRegions      = Region::getAll($db);
$listeNatures      = (new NatureOv($db))->readAll()->fetchAll(PDO::FETCH_ASSOC);
$listeMonnaies     = (new Monnaie($db))->getAll();

// ── Récupération LISTE OV avec tous les détails joints ──────────────────
$listeOV = $ovObj->getAllWithDetails();

// ── Titre de la page ────────────────────────────────────────────────────
$page_title = "Liste des ordres de virements";
?>

<?php include '../Includes/header.php'; ?>

<!-- ════ STYLES SPÉCIFIQUES ════ -->
<style>
    /* ── Filtres ──────────────────────────────────────────────────────── */
    .filter-block {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .filter-block-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: rgba(59, 158, 255, 0.06);
        border-bottom: 1px solid var(--card-border);
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--accent-blue);
        letter-spacing: 0.3px;
    }

    .filter-block-body {
        padding: 16px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-item-btn {
        justify-content: flex-end;
    }

    .filter-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-select,
    .filter-input {
        background: var(--main-bg);
        border: 1px solid var(--card-border);
        color: var(--text-main);
        padding: 8px 10px;
        border-radius: var(--radius-sm);
        font-size: 0.82rem;
        width: 100%;
        outline: none;
        transition: var(--transition);
    }

    .filter-select:focus,
    .filter-input:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 2px rgba(59, 158, 255, 0.12);
    }

    /* ── Boutons export ───────────────────────────────────────────────── */
    .btn-export {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 14px;
        border-radius: var(--radius-sm);
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--card-border);
        background: var(--card-bg);
        color: var(--text-secondary);
        transition: var(--transition);
    }

    .btn-export:hover {
        background: var(--card-bg-hover);
        color: var(--text-main);
        border-color: var(--accent-blue);
    }

    .btn-secondary-custom {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        background: rgba(59, 158, 255, 0.12);
        color: var(--accent-blue);
        border: 1px solid rgba(59, 158, 255, 0.2);
        transition: var(--transition);
    }

    .btn-secondary-custom:hover {
        background: rgba(59, 158, 255, 0.2);
    }

    /* ── Bouton action tableau ────────────────────────────────────────── */
    button.btn-action-sm {
        background: none;
        border: none;
        font-family: inherit;
        line-height: 1;
    }

    .btn-action-sm {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: var(--transition);
    }

    .btn-action-view {
        background: rgba(59, 158, 255, 0.12);
        color: var(--accent-blue);
        border: 1px solid rgba(59, 158, 255, 0.2);
    }

    .btn-action-view:hover {
        background: rgba(59, 158, 255, 0.22);
        color: var(--accent-blue);
    }

    /* ── Badges statut ────────────────────────────────────────────────── */
    .badge-pending {
        background: rgba(255, 193, 7, 0.12);
        color: #ffc107;
        border-color: rgba(255, 193, 7, 0.25) !important;
    }

    .badge-ok {
        background: rgba(34, 201, 124, 0.12);
        color: var(--accent-green);
        border-color: rgba(34, 201, 124, 0.25) !important;
    }

    /* ── Badges dynamiques (injectés par JS) ─────────────────────────── */
    .badge-gold {
        background: rgba(255, 193, 7, 0.12);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.25);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-blue {
        background: rgba(59, 158, 255, 0.12);
        color: var(--accent-blue);
        border: 1px solid rgba(59, 158, 255, 0.25);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-red {
        background: rgba(220, 53, 69, 0.12);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.25);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-muted {
        background: rgba(108, 117, 125, 0.12);
        color: var(--text-muted);
        border: 1px solid rgba(108, 117, 125, 0.2);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* ── Ligne masquée lors du filtre ─────────────────────────────────── */
    tr.row-hidden {
        display: none;
    }

    /* ── Compteur OV ──────────────────────────────────────────────────── */
    #ov-count-badge {
        font-size: 0.75rem;
        color: var(--text-muted);
        padding: 4px 10px;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-sm);
    }
</style>

<?php include '../Includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="content-area">

        <!-- ════ MESSAGES FLASH ════ -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <?php
            $swal_icon  = ($_SESSION['flash_type'] === 'danger') ? 'error' : $_SESSION['flash_type'];
            $swal_title = ($_SESSION['flash_type'] === 'success') ? 'Opération réussie' : 'Attention';
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: '<?= $swal_icon ?>',
                        title: '<?= $swal_title ?>',
                        text: '<?= addslashes($_SESSION['flash_message']) ?>',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3b9eff',
                        timer: 4000,
                        timerProgressBar: true
                    });
                });
            </script>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- ════ SECTION PRINCIPALE ════ -->
        <div class="section-card mt-3">

            <!-- ════ FILTRES DE RECHERCHE ════ -->
            <div class="filter-block mb-3">
                <div class="filter-block-header">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"
                        style="color:var(--accent-blue)">
                        <path d="M10 18h4v-2h-4v2zm-7-10v2h18V8H3zm3 7h12v-2H6v2z" />
                    </svg>
                    <span>Filtres de recherche</span>
                </div>
                <div class="filter-block-body">
                    <div class="filter-grid">

                        <!-- Fournisseur -->
                        <div class="filter-item">
                            <label class="filter-label">Fournisseur</label>
                            <select class="filter-select" id="f_fournisseur">
                                <option value="">Tous</option>
                                <?php foreach ($listeFournisseurs as $f): ?>
                                    <option value="<?= htmlspecialchars($f->getId()) ?>">
                                        <?= htmlspecialchars($f->getnom_Fournisseur()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Contrat -->
                        <div class="filter-item">
                            <label class="filter-label">Contrat</label>
                            <select class="filter-select" id="f_contrat">
                                <option value="">(Choisir un fournisseur)</option>
                            </select>
                        </div>

                        <!-- Devise -->
                        <div class="filter-item">
                            <label class="filter-label">Devise</label>
                            <select class="filter-select" id="f_devise">
                                <option value="">Toutes</option>
                                <?php foreach ($listeMonnaies as $m): ?>
                                    <option value="<?= htmlspecialchars($m['code']) ?>">
                                        <?= htmlspecialchars($m['code']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nature Facture -->
                        <div class="filter-item">
                            <label class="filter-label">Nature Facture</label>
                            <select class="filter-select" id="f_nature">
                                <option value="">Toutes</option>
                                <?php foreach ($listeNatures as $n): ?>
                                    <option value="<?= htmlspecialchars($n['code']) ?>">
                                        <?= htmlspecialchars($n['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Créateur -->
                        <div class="filter-item">
                            <label class="filter-label">Créateur</label>
                            <select class="filter-select" id="f_createur">
                                <option value="">Tous</option>
                            </select>
                        </div>

                        <!-- N° Ordre de Virement -->
                        <div class="filter-item">
                            <label class="filter-label">N° Ordre de Virement</label>
                            <input type="text" class="filter-input" id="f_num_ov" placeholder="Contient...">
                        </div>

                        <!-- Structure -->
                        <div class="filter-item">
                            <label class="filter-label">Structure</label>
                            <select class="filter-select" id="f_structure">
                                <option value="">Choisissez...</option>
                                <?php foreach ($listeRegions as $r): ?>
                                    <option value="<?= htmlspecialchars($r->getId()) ?>">
                                        <?= htmlspecialchars($r->getCode()) ?> - <?= htmlspecialchars($r->getLabel()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- N° KTP -->
                        <div class="filter-item">
                            <label class="filter-label">N° KTP</label>
                            <input type="text" class="filter-input" id="f_ktp" placeholder="Contient...">
                        </div>

                        <!-- Bouton Rechercher -->
                        <div class="filter-item filter-item-btn">
                            <button class="btn-primary-custom" id="btnRechercher"
                                style="border:none; cursor:pointer; width:100%;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                                </svg>
                                Rechercher
                            </button>
                        </div>

                    </div><!-- /filter-grid -->
                </div>
            </div>
            <!-- /Filtres -->

            <!-- ════ ACTIONS EN-TÊTE TABLEAU ════ -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn-export" id="btnExcel" title="Exporter Excel">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z" />
                        </svg>
                        Excel
                    </button>
                    <button class="btn-export" id="btnCopier" title="Copier">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
                        </svg>
                        Copier
                    </button>
                    <button class="btn-export" id="btnPDF" title="Exporter PDF">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z" />
                        </svg>
                        PDF
                    </button>
                    <!-- Compteur affiché après chargement API -->
                    <span id="ov-count-badge" style="display:none;"></span>
                </div>

                <div class="d-flex gap-2">
                    <a href="creationOV.php" class="btn-primary-custom"
                        style="border:none; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                        </svg>
                        Créer un ordre de virement
                    </a>
                    <!--
                        ════════════════════════════════════════════════════════
                        BOUTON "Charger Mes OV" — INTÉGRATION DOC 1
                        Anciennement : filtrage côté client par region_dp_id
                        Désormais    : appel API get_mes_ov.php (filtrage
                                       côté serveur par user_id SESSION)
                        ════════════════════════════════════════════════════════
                    -->
                    <button class="btn-secondary-custom" id="btnChargerMesOV" style="border:none; cursor:pointer;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                        </svg>
                        Charger Mes Ordres de Virements
                    </button>
                </div>
            </div>

            <!-- ════ TABLEAU DES OV ════ -->
            <div class="table-responsive">
                <table class="factures-table" id="tableOV">
                    <thead>
                        <tr>
                            <th>Numéro d'ordre</th>
                            <th>N° KTP</th>
                            <th>Date</th>
                            <th>Fournisseur</th>
                            <th>Contrat</th>
                            <th>Structure</th>
                            <th>Nature Facture</th>
                            <th>Montant</th>
                            <th>Monnaie</th>
                            <th>Dernier Statut</th>
                            <th>Traité par</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyOV">
                        <?php if (empty($listeOV)): ?>
                            <tr>
                                <td colspan="13">
                                    <div class="empty-state">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z" />
                                        </svg>
                                        <p>Aucun ordre de virement trouvé.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($listeOV as $ov): ?>
                                <tr data-fournisseur="<?= htmlspecialchars($ov['fournisseur_id'] ?? '') ?>"
                                    data-contrat="<?= htmlspecialchars($ov['contrat_id'] ?? '') ?>"
                                    data-devise="<?= htmlspecialchars($ov['money_code'] ?? '') ?>"
                                    data-nature="<?= htmlspecialchars($ov['nature_code'] ?? '') ?>"
                                    data-num-ov="<?= htmlspecialchars(strtolower($ov['Num_OV'] ?? '')) ?>"
                                    data-ktp="<?= htmlspecialchars($ov['Num_KTP'] ?? '') ?>"
                                    data-structure="<?= htmlspecialchars($ov['region_dpid'] ?? '') ?>">
                                    <td><strong style="color:var(--accent-blue);"><?= htmlspecialchars($ov['Num_OV'] ?? '') ?></strong></td>
                                    <td style="color:var(--text-muted);"><?= htmlspecialchars($ov['Num_KTP'] ?? '') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($ov['Date_OV'])) ?? '') ?></td>
                                    <td><strong style="color:var(--text-main);"><?= htmlspecialchars($ov['nom_Fournisseur'] ?? '-') ?></strong></td>
                                    <td><?= htmlspecialchars($ov['num_Contrat'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge-status badge-process">
                                            <?= htmlspecialchars($ov['region_code'] ?? '') ?>-DP
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($ov['nature_label'] ?? '') ?></td>
                                    <td>
                                        <strong style="color:var(--accent-green);">
                                            <?= number_format($ov['montant_total'] ?? 0, 2, '.', ' ') ?>
                                        </strong>
                                    </td>
                                    <td><?= htmlspecialchars($ov['money_code'] ?? '') ?></td>
                                    <td>
                                        <?php
                                        $statut = $ov['dernier_statut'] ?? 'Brouillon';
                                        $statut_class = 'badge-pending';
                                        if ($statut === 'Validé')  $statut_class = 'badge-ok';
                                        elseif ($statut === 'Envoyé')  $statut_class = 'badge-process';
                                        elseif ($statut === 'Exécuté') $statut_class = 'badge-ok';
                                        ?>
                                        <span class="badge-status <?= $statut_class ?>">
                                            <?= htmlspecialchars($statut) ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--text-secondary);"><?= htmlspecialchars($ov['traite_par'] ?? 'SIEGE HAOUES') ?></td>
                                    <td style="color:var(--text-muted); font-size:0.8rem;"><?= htmlspecialchars($ov['type_ov'] ?? 'Ordre de virement Fournisseurs Étrangers') ?></td>
                                    <td>
                                        <!-- ✅ Navigation POST sécurisée — aucun ov_id dans l'URL -->
                                        <form method="POST" action="OV_detailles.php" style="display:inline;">
                                            <input type="hidden" name="ov_id" value="<?= (int)($ov['id'] ?? 0) ?>">
                                            <button type="submit" class="btn-action-sm btn-action-view" title="Consulter">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                                                </svg>
                                                Consulter
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- /Tableau -->

        </div><!-- /section-card -->

        <?php include '../Includes/footer.php'; ?>
    </div><!-- /content-area -->
</div><!-- /main-content -->

<!-- ════════════════════════════════════════════════════════════════
     SCRIPTS INTÉGRÉS
     ════════════════════════════════════════════════════════════════ -->
<script>
$(document).ready(function () {

    // ════════════════════════════════════════════════════════════════
    // ── UTILITAIRES (Doc 1) ─────────────────────────────────────────
    // ════════════════════════════════════════════════════════════════

    /**
     * Wrapper fetch POST (FormData).
     * Utilisé par chargerMesOV() pour appeler get_mes_ov.php.
     */
    function postAPI(url, data) {
        const fd = new FormData();
        Object.keys(data).forEach(k => fd.append(k, data[k]));
        return fetch(url, { method: 'POST', body: fd }).then(r => r.json());
    }

    /** Échappe le HTML pour éviter les injections XSS dans les lignes injectées par JS. */
    function esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /** Formate une date ISO → JJ/MM/AAAA. */
    function fmtDate(d) {
        if (!d) return '–';
        try {
            const x = new Date(d);
            return `${String(x.getDate()).padStart(2,'0')}/${String(x.getMonth()+1).padStart(2,'0')}/${x.getFullYear()}`;
        } catch (e) { return d; }
    }

    /**
     * Retourne la classe CSS du badge selon le statut.
     * Aligné avec les classes .badge-gold / .badge-blue / .badge-red / .badge-muted
     * définies dans les styles ci-dessus.
     */
    function badgeClass(s) {
        if (!s) return 'badge-muted';
        const l = s.toLowerCase();
        if (l.includes('cours') || l.includes('trait') || l.includes('brouillon')) return 'badge-gold';
        if (l.includes('valid') || l.includes('exécut') || l.includes('atf'))       return 'badge-blue';
        if (l.includes('annul') || l.includes('rejet'))                              return 'badge-red';
        return 'badge-muted';
    }


    // ════════════════════════════════════════════════════════════════
    // ── RENDU DYNAMIQUE DES LIGNES (Doc 1, adapté aux 13 colonnes) ──
    // ════════════════════════════════════════════════════════════════

    /**
     * Injecte les OV retournés par l'API dans le tableau.
     * Respecte la structure des 13 colonnes du tableau PHP (Doc 2).
     * Les lignes reçoivent les mêmes data-attributes que les lignes
     * PHP afin que le filtre côté-client (btnRechercher) continue
     * de fonctionner sur elles.
     *
     * @param {Array} ovs — tableau d'objets retourné par get_mes_ov.php
     */
    function renderTableauOV(ovs) {
        const tbody = document.getElementById('tbodyOV');
        if (!tbody) return;

        if (!ovs || ovs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="13">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/>
                            </svg>
                            <p>Aucun ordre de virement trouvé pour votre compte.</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = ovs.map(ov => `
            <tr
                data-fournisseur="${esc(ov.fournisseur_id  ?? '')}"
                data-contrat="${esc(ov.contrat_id       ?? '')}"
                data-devise="${esc(ov.money_code        ?? '')}"
                data-nature="${esc(ov.nature_code       ?? '')}"
                data-num-ov="${esc((ov.Num_OV ?? '').toLowerCase())}"
                data-ktp="${esc(ov.Num_KTP           ?? '')}"
                data-structure="${esc(ov.region_dpid      ?? '')}">

                <td><strong style="color:var(--accent-blue);font-family:monospace;">${esc(ov.Num_OV)}</strong></td>
                <td style="color:var(--text-muted);font-family:monospace;">${esc(ov.Num_KTP)}</td>
                <td>${fmtDate(ov.Date_OV)}</td>
                <td><strong style="color:var(--text-main);">${esc(ov.nom_Fournisseur || '–')}</strong></td>
                <td>${esc(ov.num_Contrat || '–')}</td>
                <td>
                    <span class="badge-status badge-process">
                        ${esc(ov.region_code || '')}${ov.region_code ? '-DP' : '–'}
                    </span>
                </td>
                <td>${esc(ov.nature_label || '–')}</td>
                <td>
                    <strong style="color:var(--accent-green);">
                        ${ov.montant_total
                            ? parseFloat(ov.montant_total).toLocaleString('fr-FR', {minimumFractionDigits:2, maximumFractionDigits:2})
                            : '0.00'}
                    </strong>
                </td>
                <td>${esc(ov.money_code || '–')}</td>
                <td>
                    <span class="${badgeClass(ov.dernier_statut)}">
                        ${esc(ov.dernier_statut || 'Brouillon')}
                    </span>
                </td>
                <td style="color:var(--text-secondary);">${esc(ov.traite_par || 'SIEGE HAOUES')}</td>
                <td style="color:var(--text-muted);font-size:0.8rem;">${esc(ov.type_ov || 'Ordre de virement Fournisseurs Étrangers')}</td>
                <td>
                    <!-- ✅ POST sécurisé — pas d'ov_id dans l'URL -->
                    <form method="POST" action="OV_detailles.php" style="display:inline;">
                        <input type="hidden" name="ov_id" value="${parseInt(ov.id || 0)}">
                        <button type="submit" class="btn-action-sm btn-action-view" title="Consulter">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                            Consulter
                        </button>
                    </form>
                </td>
            </tr>
        `).join('');
    }


    // ════════════════════════════════════════════════════════════════
    // ── 1. CASCADE : Fournisseur → Contrats (Doc 2) ─────────────────
    // ════════════════════════════════════════════════════════════════
    $('#f_fournisseur').on('change', function () {
        const id = $(this).val();
        const $c = $('#f_contrat');
        $c.html('<option value="">Chargement...</option>');

        if (!id) {
            $c.html('<option value="">(Choisir un fournisseur)</option>');
            return;
        }

        $.ajax({
            url: '../../Controllers/LOCAL_API/Contrat/get_contrats_by_fournisseur.php',
            type: 'POST',
            data: { fournisseur_id: id },
            dataType: 'json',
            success: function (data) {
                $c.html('<option value="">Tous les contrats</option>');
                if (data && data.length > 0) {
                    data.forEach(function (c) {
                        $c.append(`<option value="${c.id}">${c.num_contrat}</option>`);
                    });
                } else {
                    $c.html('<option value="">Aucun contrat</option>');
                }
            },
            error: function () {
                $c.html('<option value="">Erreur</option>');
            }
        });
    });


    // ════════════════════════════════════════════════════════════════
    // ── 2. BOUTON "Charger Mes OV" — INTÉGRATION DOC 1 ──────────────
    //
    //    AVANT (Doc 2) : filtrage côté client par data-structure
    //                    comparé à region_dp_id SESSION.
    //
    //    APRÈS (intégré) : appel POST vers get_mes_ov.php.
    //                      L'user_id est lu depuis la SESSION PHP,
    //                      jamais envoyé en POST (sécurité).
    //                      Le tableau est entièrement remplacé par
    //                      les résultats filtrés côté serveur.
    // ════════════════════════════════════════════════════════════════
const API_OV = '/asoutryv2/asmatry/Controllers/LOCAL_API/OV/';

    $('#btnChargerMesOV').on('click', function () {
        // Feedback visuel immédiat
        const tbody = document.getElementById('tbodyOV');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="13" style="text-align:center;padding:30px;color:var(--text-muted);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="animation:spin 1s linear infinite;vertical-align:middle;margin-right:8px;">
                            <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
                        </svg>
                        Chargement de vos ordres de virements…
                    </td>
                </tr>`;
        }

        // Masquer le compteur pendant le chargement
        $('#ov-count-badge').hide();

        // ── Appel API — user_id résolu côté SESSION PHP ─────────────
        postAPI(API_OV + 'get_mes_ov.php', {})
            .then(function (res) {
                if (!res.success) {
                    // Afficher le message d'erreur de l'API
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur de chargement',
                            text: res.message || 'Impossible de charger vos OV.',
                            confirmButtonColor: '#3b9eff'
                        });
                    } else {
                        alert(res.message || 'Erreur de chargement.');
                    }
                    // Restaurer un état vide propre
                    if (tbody) {
                        tbody.innerHTML = `<tr><td colspan="13" style="text-align:center;padding:20px;color:#888;">Erreur — réessayez.</td></tr>`;
                    }
                    return;
                }

                // ── Injecter les lignes dans le tableau ────────────
                renderTableauOV(res.ovs);

                // ── Afficher le compteur ───────────────────────────
                const total = res.total ?? (res.ovs ? res.ovs.length : 0);
                $('#ov-count-badge')
                    .text(total + ' OV trouvé' + (total > 1 ? 's' : '') + ' pour votre compte')
                    .show();
            })
            .catch(function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur réseau',
                        text: 'La requête vers get_mes_ov.php a échoué.',
                        confirmButtonColor: '#3b9eff'
                    });
                } else {
                    alert('Erreur réseau.');
                }
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="13" style="text-align:center;padding:20px;color:#888;">Erreur réseau — réessayez.</td></tr>`;
                }
            });
    });


    // ════════════════════════════════════════════════════════════════
    // ── 3. BOUTON "Rechercher" — filtre côté client (Doc 2) ─────────
    // ════════════════════════════════════════════════════════════════
    $('#btnRechercher').on('click', function () {
        filtrerTableau();
    });

    /**
     * Filtre les lignes visibles en fonction des valeurs des selects/inputs.
     * Fonctionne sur les lignes PHP initiales ET sur celles injectées
     * dynamiquement par renderTableauOV() (même data-attributes).
     */
    function filtrerTableau() {
        const fournisseur = $('#f_fournisseur').val();
        const contrat     = $('#f_contrat').val();
        const devise      = $('#f_devise').val();
        const nature      = $('#f_nature').val();
        const numOV       = $('#f_num_ov').val().toLowerCase().trim();
        const ktp         = $('#f_ktp').val().toLowerCase().trim();
        const structure   = $('#f_structure').val();

        let count = 0;
        $('#tbodyOV tr').each(function () {
            const $row = $(this);
            // Ne pas filtrer la ligne "empty-state"
            if ($row.find('td[colspan]').length) return;

            const okFournisseur = !fournisseur || $row.data('fournisseur') == fournisseur;
            const okContrat     = !contrat     || $row.data('contrat')     == contrat;
            const okDevise      = !devise      || $row.data('devise')      === devise;
            const okNature      = !nature      || $row.data('nature')      === nature;
            const okNumOV       = !numOV       || String($row.data('num-ov')).toLowerCase().includes(numOV);
            const okKTP         = !ktp         || String($row.data('ktp')).toLowerCase().includes(ktp);
            const okStructure   = !structure   || $row.data('structure')   == structure;

            const visible = okFournisseur && okContrat && okDevise && okNature
                         && okNumOV && okKTP && okStructure;

            $row.toggleClass('row-hidden', !visible);
            if (visible) count++;
        });
    }


    // ════════════════════════════════════════════════════════════════
    // ── 4. EXPORT COPIER (Doc 2) ─────────────────────────────────────
    // ════════════════════════════════════════════════════════════════
    $('#btnCopier').on('click', function () {
        let text = '';
        $('#tbodyOV tr:not(.row-hidden)').each(function () {
            let row = [];
            $(this).find('td').each(function () {
                row.push($(this).text().trim());
            });
            text += row.join('\t') + '\n';
        });
        navigator.clipboard.writeText(text).then(function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Copié !',
                    text: 'Les données ont été copiées dans le presse-papiers.',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            } else {
                alert('Données copiées dans le presse-papiers !');
            }
        });
    });

});

/* Animation spinner pour le chargement */
const _style = document.createElement('style');
_style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(_style);
</script>