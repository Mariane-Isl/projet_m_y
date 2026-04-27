<?php
session_start();
$page_title = "Recap Ordres de Virement";

if (empty($_SESSION['user_name'])) {
    header('Location: ../login/login.php');
    exit;
}
?>
<?php require_once '../Includes/header.php'; ?>
<?php require_once '../Includes/sidebar.php'; ?>

<style>
    /* ── Breadcrumb ── */
    .breadcrumb-bar {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .breadcrumb-bar a {
        color: var(--text-muted);
        transition: var(--transition);
    }

    .breadcrumb-bar a:hover {
        color: var(--accent-blue);
    }

    .breadcrumb-bar svg {
        width: 12px;
        height: 12px;
        opacity: .5;
    }

    /* ── Filtres ── */
    .filter-block {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-md);
        overflow: hidden;
        margin-bottom: 22px;
    }

    .filter-block-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        background: rgba(245, 166, 35, .06);
        border-bottom: 1px solid var(--card-border);
        font-size: 0.79rem;
        font-weight: 600;
        color: #f5a623;
        letter-spacing: .3px;
    }

    .filter-block-header svg {
        width: 15px;
        height: 15px;
    }

    .filter-body {
        padding: 18px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
        gap: 14px;
        align-items: end;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-label {
        font-size: 0.71rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .filter-ctrl {
        background: var(--main-bg);
        border: 1px solid var(--card-border);
        color: var(--text-main);
        padding: 8px 11px;
        border-radius: var(--radius-sm);
        font-size: 0.82rem;
        width: 100%;
        outline: none;
        transition: var(--transition);
        -webkit-appearance: none;
        appearance: none;
    }

    .filter-ctrl:focus {
        border-color: #f5a623;
        box-shadow: 0 0 0 2px rgba(245, 166, 35, .12);
    }

    .filter-ctrl option {
        background: var(--card-bg);
        color: var(--text-main);
    }

    /* ── Boutons ── */
    .btn-row {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 16px;
        background: transparent;
        color: var(--text-secondary);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-sm);
        font-size: 0.81rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-reset:hover {
        background: rgba(255, 255, 255, .04);
        color: var(--text-main);
    }

    .btn-search {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 20px;
        background: #f5a623;
        color: #000;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 0.81rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-search:hover {
        background: #ffd166;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(245, 166, 35, .35);
    }

    .btn-pdf {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 16px;
        background: rgba(240, 82, 82, .1);
        color: var(--accent-red);
        border: 1px solid rgba(240, 82, 82, .2);
        border-radius: var(--radius-sm);
        font-size: 0.81rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-pdf:hover {
        background: rgba(240, 82, 82, .18);
    }

    /* ── Totaux ── */
    .totaux-row {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .total-pill {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-sm);
        padding: 11px 18px;
        min-width: 180px;
    }

    .total-pill-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(34, 201, 124, .12);
        color: var(--accent-green);
    }

    .total-pill-icon svg {
        width: 17px;
        height: 17px;
    }

    .total-pill-label {
        font-size: .68rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .total-pill-value {
        font-size: .92rem;
        font-weight: 700;
        color: var(--text-main);
        font-family: 'Space Grotesk', sans-serif;
    }

    .total-pill.amber .total-pill-icon {
        background: rgba(245, 166, 35, .12);
        color: #f5a623;
    }

    .total-pill.blue .total-pill-icon {
        background: rgba(59, 158, 255, .12);
        color: var(--accent-blue);
    }

    /* ── Résultats ── */
    .results-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .results-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        border-bottom: 1px solid var(--card-border);
    }

    .results-title {
        font-size: .81rem;
        font-weight: 700;
        color: var(--text-secondary);
    }

    .results-count {
        font-size: .72rem;
        font-weight: 600;
        padding: 3px 10px;
        background: rgba(245, 166, 35, .1);
        color: #f5a623;
        border-radius: 20px;
    }

    .btn-export-excel {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        background: rgba(34, 201, 124, .1);
        color: var(--accent-green);
        border: 1px solid rgba(34, 201, 124, .2);
        border-radius: var(--radius-sm);
        font-size: .77rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-export-excel:hover {
        background: rgba(34, 201, 124, .18);
    }

    /* ── Table ── */
    #tableRecapOV td,
    #tableRecapOV th {
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid var(--card-border) !important;
        padding: 12px 14px !important;
        color: var(--text-main) !important;
        font-size: .82rem;
        vertical-align: middle;
    }

    #tableRecapOV thead th {
        color: var(--text-muted) !important;
        font-size: .69rem !important;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--card-border) !important;
    }

    #tableRecapOV tbody tr:hover td {
        background: rgba(255, 255, 255, .02) !important;
    }

    /* Badges statut OV */
    .badge-ov {
        display: inline-block;
        padding: 4px 11px;
        border-radius: 20px;
        font-size: .69rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .ov-trait {
        background: rgba(59, 158, 255, .12);
        color: var(--accent-blue);
    }

    .ov-atf {
        background: rgba(245, 166, 35, .12);
        color: #f5a623;
    }

    .ov-adb_atf {
        background: rgba(245, 166, 35, .15);
        color: #ffd166;
    }

    .ov-annul {
        background: rgba(240, 82, 82, .12);
        color: var(--accent-red);
    }

    .ov-default {
        background: rgba(255, 255, 255, .07);
        color: var(--text-secondary);
    }

    /* DataTables overrides */
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background: var(--main-bg) !important;
        border: 1px solid var(--card-border) !important;
        color: var(--text-main) !important;
        border-radius: var(--radius-sm) !important;
        padding: 5px 10px !important;
        font-size: .8rem !important;
        outline: none !important;
    }

    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_info {
        color: var(--text-muted) !important;
        font-size: .77rem;
    }

    .dataTables_wrapper .paginate_button {
        background: var(--card-bg) !important;
        border: 1px solid var(--card-border) !important;
        color: var(--text-secondary) !important;
        border-radius: var(--radius-sm) !important;
        padding: 4px 12px !important;
        font-size: .78rem !important;
        cursor: pointer;
    }

    .dataTables_wrapper .paginate_button.current,
    .dataTables_wrapper .paginate_button:hover {
        background: #f5a623 !important;
        color: #000 !important;
        border-color: #f5a623 !important;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #f5a623 !important;
        box-shadow: 0 0 0 2px rgba(245, 166, 35, .12) !important;
    }

    /* SweetAlert dark */
    .swal2-popup {
        background: var(--card-bg) !important;
        color: var(--text-main) !important;
    }

    .swal2-title {
        color: var(--text-main) !important;
    }

    /* Bouton Détail OV (NOUVEAU - Theme Orange) */
    .btn-detail {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        background: rgba(245, 166, 35, .1);
        color: #f5a623;
        border: 1px solid rgba(245, 166, 35, .2);
        border-radius: var(--radius-sm);
        font-size: .72rem;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        white-space: nowrap;
        cursor: pointer; /* Important pour les boutons */
    }
    .btn-detail:hover {
        background: rgba(245, 166, 35, .18);
        color: #f5a623;
    }
</style>

<div class="main-content">
    <?php require_once '../Includes/topbar.php'; ?>

    <div class="content-area">

        <!-- Breadcrumb -->
        <div class="breadcrumb-bar">
            <a href="<?= linkTo('Pages/Dashboard/tableau_de_bord.php') ?>">Accueil</a>
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" />
            </svg>
            <a href="<?= linkTo('Pages/Dashboard/tableau_de_bord.php') ?>">Tableau de Bord</a>
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" />
            </svg>
            <span>Recap Ordres Virement</span>
        </div>

        <!-- ══ Filtres ══ -->
        <div class="filter-block">
            <div class="filter-block-header">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z" />
                </svg>
                Filtres de recherche
            </div>
            <div class="filter-body">
                <div class="filter-grid">

                    <!-- Fournisseur -->
                    <div class="filter-item">
                        <label class="filter-label">Fournisseur</label>
                        <select id="sel_fournisseur" class="filter-ctrl">
                            <option value="">Sélectionnez...</option>
                        </select>
                    </div>

                    <!-- Contrat -->
                    <div class="filter-item">
                        <label class="filter-label">Contrat</label>
                        <select id="sel_contrat" class="filter-ctrl" disabled>
                            <option value="">(Fournisseur requis)</option>
                        </select>
                    </div>

                    <!-- Monnaie -->
                    <div class="filter-item">
                        <label class="filter-label">Monnaie</label>
                        <select id="sel_monnaie" class="filter-ctrl">
                            <option value="">Sélectionnez...</option>
                        </select>
                    </div>

                    <!-- Statut OV -->
                    <div class="filter-item">
                        <label class="filter-label">Statut</label>
                        <select id="sel_statut_ov" class="filter-ctrl">
                            <option value="">Sélectionnez...</option>
                        </select>
                    </div>

                    <!-- Structure -->
                    <div class="filter-item">
                        <label class="filter-label">Structure</label>
                        <select id="sel_structure" class="filter-ctrl">
                            <option value="">Sélectionnez...</option>
                        </select>
                    </div>

                    <!-- Agent -->
                    <div class="filter-item">
                        <label class="filter-label">Agent Traitant</label>
                        <select id="sel_agent" class="filter-ctrl">
                            <option value="">Tous les agents...</option>
                        </select>
                    </div>

                </div>

                <!-- Boutons -->
                <div class="btn-row">
                    <button class="btn-reset" id="btn_reset">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z" />
                        </svg>
                        Réinitialiser
                    </button>
                    <button class="btn-search" id="btn_search">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                        </svg>
                        Rechercher
                    </button>
                    <button class="btn-pdf" id="btn_pdf">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z" />
                        </svg>
                        Rapport PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ Totaux ══ -->
        <div class="totaux-row" id="totaux-row" style="display:none;"></div>

        <!-- ══ Résultats ══ -->
        <div class="results-card">
            <div class="results-header">
                <span class="results-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:5px;">
                        <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z" />
                    </svg>
                    Résultats de la recherche
                </span>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="results-count" id="results-count">0 OV</span>
                    <button class="btn-export-excel" id="btn_excel" style="display:none;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                        </svg>
                        Exporter Excel
                    </button>
                </div>
            </div>
            <div style="padding:16px 18px;overflow-x:auto;">
                <table id="tableRecapOV" class="table w-100 mb-0">
                    <thead>
                        <tr>
                            <th>Ordre Virement</th>
                            <th>Date OV</th>
                            <th>Fournisseur</th>
                            <th>Contrat</th>
                            <th>Structure</th>
                            <th>Montant</th>
                            <th>Monnaie</th>
                            <th>Nb Factures</th>
                            <th>Statut</th>
                            <th>Dernier traitement</th>
                            <th>Agent</th>
                            <th>Action</th> <!-- Colonne Action -->
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        <tr>
                            <td colspan="12" style="text-align:center;color:var(--text-muted);padding:30px!important;">
                                Sélectionnez vos critères et cliquez sur Rechercher.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Formulaire caché pour la navigation en POST avec SOURCE RECAP -->
        <form id="formPostDetails" action="../OV/OV_detailles.php" method="POST" style="display: none;">
            <input type="hidden" name="id" id="postOvId" value="">
            <input type="hidden" name="source" value="recap"> <!-- IMPORTANT ICI -->
        </form>

        <?php require_once '../Includes/footer.php'; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        const API = '<?= linkTo('Controllers/LOCAL_API/Dashboard/dashboard_api.php') ?>';
        let dtInstance = null;

        // ── Charger selects ──────────────────────────────────────────────────
        function loadSelect(action, selectId, labelField, valueField) {
            fetch(API, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    const sel = document.getElementById(selectId);
                    const first = sel.options[0].text;
                    sel.innerHTML = `<option value="">${first}</option>`;
                    res.data.forEach(item => {
                        sel.innerHTML += `<option value="${item[valueField]}">${item[labelField]}</option>`;
                    });
                });
        }

        loadSelect('get_fournisseurs', 'sel_fournisseur', 'Nom_Fournisseur', 'id');
        loadSelect('get_monnaies', 'sel_monnaie', 'code', 'id');
        loadSelect('get_statuts_ov', 'sel_statut_ov', 'label', 'id');
        loadSelect('get_structures', 'sel_structure', 'label', 'id');
        loadSelect('get_gestionnaires', 'sel_agent', 'nom_complet', 'id');

        // ── Cascade fournisseur → contrat ─────────────────────────────────────
        $('#sel_fournisseur').on('change', function() {
            const fid = $(this).val();
            const sel = $('#sel_contrat');
            if (!fid) {
                sel.html('<option value="">(Fournisseur requis)</option>').prop('disabled', true);
                return;
            }
            sel.prop('disabled', false).html('<option value="">Chargement…</option>');
            fetch(API, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'get_contrats_by_fournisseur',
                        fournisseur_id: fid
                    })
                })
                .then(r => r.json())
                .then(res => {
                    let html = '<option value="">Tous les contrats</option>';
                    if (res.data && res.data.length) {
                        res.data.forEach(c => {
                            html += `<option value="${c.id}">${c.num_Contrat}</option>`;
                        });
                    }
                    sel.html(html);
                });
        });

        // ── Helpers ───────────────────────────────────────────────────────────
        function ovBadge(code, label) {
            const map = {
                TRAIT: 'ov-trait',
                ATF: 'ov-atf',
                ADB_ATF: 'ov-adb_atf',
                ANNUL: 'ov-annul'
            };
            const cls = map[(code || '').toUpperCase()] || 'ov-default';
            return label ? `<span class="badge-ov ${cls}">${label}</span>` : '<span style="color:var(--text-muted)">—</span>';
        }

        function fmtDate(d) {
            return d ? new Date(d).toLocaleDateString('fr-DZ') : '—';
        }

        function fmtMontant(n) {
            return parseFloat(n || 0).toLocaleString('fr-DZ', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // ── Rechercher ────────────────────────────────────────────────────────
        function doSearch() {
            const body = new URLSearchParams({
                action: 'recap_ov',
                fournisseur_id: $('#sel_fournisseur').val(),
                contrat_id: $('#sel_contrat').val(),
                monnaie_id: $('#sel_monnaie').val(),
                statut_ov_id: $('#sel_statut_ov').val(),
                structure_id: $('#sel_structure').val(),
                agent_id: $('#sel_agent').val(), // On envoie l'ID maintenant !
            });

            $('#btn_search').prop('disabled', true).text('Recherche…');

            fetch(API, {
                    method: 'POST',
                    body
                })
                .then(r => r.json())
                .then(res => {
                    $('#btn_search').prop('disabled', false).html(`
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    Rechercher`);

                    if (!res.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: res.error || 'Erreur serveur',
                            background: '#1a1d27',
                            color: '#e4e8f1'
                        });
                        return;
                    }

                    // ── Totaux ──
                    const totRow = document.getElementById('totaux-row');
                    totRow.style.display = 'flex';
                    totRow.innerHTML = '';
                    const colorMap = {
                        DZD: 'blue',
                        USD: 'amber',
                        EURO: '',
                        EUR: ''
                    };
                    Object.entries(res.totaux).forEach(([mon, val]) => {
                        const cls = colorMap[mon] || '';
                        totRow.innerHTML += `
                        <div class="total-pill ${cls}">
                            <div class="total-pill-icon">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                            </div>
                            <div>
                                <div class="total-pill-label">Total ${mon}</div>
                                <div class="total-pill-value">${fmtMontant(val)} ${mon}</div>
                            </div>
                        </div>`;
                    });

                    // ── Table ──
                    if (dtInstance) {
                        dtInstance.destroy();
                        dtInstance = null;
                    }

                    let html = '';
                    res.data.forEach(r => {
                        html += `<tr>
                        <td><strong style="color:#f5a623;font-size:.8rem;">${r.Num_OV}</strong></td>
                        <td>${fmtDate(r.date_ov)}</td>
                        <td>${r.Nom_Fournisseur || '—'}</td>
                        <td><span style="color:var(--text-muted);font-size:.78rem;">${r.num_Contrat || '—'}</span></td>
                        <td>${r.structure || '—'}</td>
                        <td><strong>${fmtMontant(r.montant_total)}</strong></td>
                        <td><span style="font-size:.7rem;padding:2px 7px;background:rgba(255,255,255,.07);border-radius:4px;">${r.monnaie || '—'}</span></td>
                        <td><span style="font-size:.78rem;color:var(--text-muted);">${r.nb_factures || 0}</span></td>
                        <td>${ovBadge(r.statut_ov_code, r.statut_ov)}</td>
                        <td style="font-size:.76rem;color:var(--text-muted);">${fmtDate(r.dernier_traitement)}</td>
                        <td>${r.agent ? r.agent.trim() : '—'}</td>
                        
                        <!-- NOUVELLE COLONNE ACTION (POST) -->
                        <td>
                            <button type="button" class="btn-detail btn-post-submit" data-id="${r.id}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                Détails
                            </button>
                        </td>
                    </tr>`;
                    });

                    if (!html) {
                        html = `<tr><td colspan="12" style="text-align:center;color:var(--text-muted);padding:30px!important;">Aucun résultat trouvé.</td></tr>`;
                    }

                    document.getElementById('table-body').innerHTML = html;
                    document.getElementById('results-count').textContent = res.count + ' OV';
                    document.getElementById('btn_excel').style.display = res.count > 0 ? 'inline-flex' : 'none';

                    if (res.count > 0) {
                        dtInstance = $('#tableRecapOV').DataTable({
                            pageLength: 20,
                            lengthMenu: [10, 20, 50, 100],
                            order: [
                                [1, 'desc']
                            ],
                            language: {
                                search: 'Rechercher :',
                                emptyTable: '—',
                                zeroRecords: 'Aucun résultat',
                                info: 'Affichage _START_ à _END_ sur _TOTAL_',
                                infoEmpty: '0 résultat',
                                paginate: {
                                    previous: '‹',
                                    next: '›'
                                },
                                lengthMenu: 'Afficher _MENU_'
                            },
                            columnDefs: [{
                                orderable: false,
                                targets: [11] // Désactive le tri sur la colonne Action
                            }]
                        });
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Recherche terminée',
                        text: res.count + ' OV trouvé(s).',
                        timer: 1800,
                        showConfirmButton: false,
                        background: '#1a1d27',
                        color: '#e4e8f1'
                    });
                })
                .catch(() => {
                    $('#btn_search').prop('disabled', false).text('Rechercher');
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur réseau',
                        background: '#1a1d27',
                        color: '#e4e8f1'
                    });
                });
        }

        $('#btn_search').on('click', doSearch);

        // ── Reset ─────────────────────────────────────────────────────────────
        $('#btn_reset').on('click', function() {
            $('#sel_fournisseur, #sel_monnaie, #sel_statut_ov, #sel_structure, #sel_agent').val('');
            $('#sel_contrat').html('<option value="">(Fournisseur requis)</option>').prop('disabled', true);
            $('#inp_agent').val('');
            $('#totaux-row').hide();
            if (dtInstance) {
                dtInstance.destroy();
                dtInstance = null;
            }
            document.getElementById('table-body').innerHTML = `<tr><td colspan="12" style="text-align:center;color:var(--text-muted);padding:30px!important;">Sélectionnez vos critères et cliquez sur Rechercher.</td></tr>`;
            document.getElementById('results-count').textContent = '0 OV';
            document.getElementById('btn_excel').style.display = 'none';
        });

        // ── Navigation POST vers les détails ──────────────────────────────────
        $('#tableRecapOV').on('click', '.btn-post-submit', function() {
            const ovId = $(this).data('id');
            $('#postOvId').val(ovId);
            $('#formPostDetails').submit();
        });

        // ── Export Excel ──────────────────────────────────────────────────────
        $('#btn_excel').on('click', function() {
            const table = document.getElementById('tableRecapOV');
            let csv = [];
            table.querySelectorAll('tr').forEach(row => {
                const cols = [];
                row.querySelectorAll('th, td').forEach((cell, index) => {
                    // On ne veut pas exporter la dernière colonne (le bouton action)
                    if (index < 11) {
                        cols.push('"' + cell.innerText.replace(/"/g, '""') + '"');
                    }
                });
                if (cols.length > 0) csv.push(cols.join(','));
            });
            const blob = new Blob(['\uFEFF' + csv.join('\n')], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'recap_ov_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
        });

        // ── PDF ───────────────────────────────────────────────────────────────
        $('#btn_pdf').on('click', function() {
            window.print();
        });
    });
</script>
