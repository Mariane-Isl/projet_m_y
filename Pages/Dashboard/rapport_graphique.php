<?php
session_start();
$page_title = "Situation Graphique";
if (empty($_SESSION['user_name'])) {
    header('Location: ../login/login.php');
    exit;
}
?>
<?php require_once '../Includes/header.php'; ?>
<?php require_once '../Includes/sidebar.php'; ?>

<?php
// Charger Chart.js depuis dist local si dispo, sinon CDN
$projectRoot = realpath(__DIR__ . '/../../');
$docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');
$baseUrl     = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    /* ── Breadcrumb ── */
    .breadcrumb-bar {
        font-size: .75rem;
        color: var(--text-muted);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .breadcrumb-bar a {
        color: var(--text-muted);
        transition: var(--transition);
    }

    .breadcrumb-bar a:hover {
        color: var(--accent-violet);
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
        margin-bottom: 20px;
    }

    .filter-block-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: rgba(155, 109, 255, .07);
        border-bottom: 1px solid var(--card-border);
        font-size: .79rem;
        font-weight: 600;
        color: var(--accent-violet);
        letter-spacing: .3px;
    }

    .filter-block-header svg {
        width: 14px;
        height: 14px;
    }

    .filter-body {
        padding: 16px 18px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto;
        gap: 13px;
        align-items: end;
    }

    @media(max-width:1100px) {
        .filter-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media(max-width:700px) {
        .filter-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .filter-label {
        font-size: .7rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .filter-ctrl {
        background: var(--main-bg);
        border: 1px solid var(--card-border);
        color: var(--text-main);
        padding: 7px 10px;
        border-radius: var(--radius-sm);
        font-size: .81rem;
        width: 100%;
        outline: none;
        transition: var(--transition);
        -webkit-appearance: none;
        appearance: none;
    }

    .filter-ctrl:focus {
        border-color: var(--accent-violet);
        box-shadow: 0 0 0 2px rgba(155, 109, 255, .14);
    }

    .filter-ctrl option {
        background: var(--card-bg);
        color: var(--text-main);
    }

    .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: rgba(255, 255, 255, .04);
        color: var(--text-secondary);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-sm);
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-reset:hover {
        background: rgba(255, 255, 255, .08);
        color: var(--text-main);
    }

    .btn-reset svg {
        width: 13px;
        height: 13px;
    }

    /* ── Layout 2 colonnes ── */
    .graphs-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }

    @media(max-width:900px) {
        .graphs-row {
            grid-template-columns: 1fr;
        }
    }

    /* ── Carte graphique ── */
    .graph-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .graph-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-bottom: 1px solid var(--card-border);
        background: rgba(255, 255, 255, .02);
    }

    .graph-card-title {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: .79rem;
        font-weight: 700;
        color: var(--text-secondary);
    }

    .graph-card-title svg {
        width: 15px;
        height: 15px;
        color: var(--accent-violet);
    }

    .graph-card-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-charger {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 13px;
        background: #f5a623;
        color: #000;
        border: none;
        border-radius: var(--radius-sm);
        font-size: .75rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-charger:hover {
        background: #ffd166;
        transform: translateY(-1px);
    }

    .btn-charger svg {
        width: 13px;
        height: 13px;
    }

    .btn-charger.violet {
        background: var(--accent-violet);
        color: #fff;
    }

    .btn-charger.violet:hover {
        background: #b98aff;
    }

    .btn-expand {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        background: rgba(255, 255, 255, .05);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-sm);
        color: var(--text-muted);
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-expand:hover {
        background: rgba(255, 255, 255, .1);
        color: var(--text-main);
    }

    .btn-expand svg {
        width: 13px;
        height: 13px;
    }

    .graph-body {
        padding: 14px 16px;
    }

    .graph-subtitle {
        font-size: .71rem;
        text-align: center;
        color: var(--text-muted);
        margin-bottom: 10px;
        line-height: 1.5;
    }

    .graph-subtitle strong {
        color: var(--accent-violet);
    }

    .chart-container {
        position: relative;
        width: 100%;
        height: 260px;
    }

    .chart-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 200px;
        color: var(--text-muted);
        font-size: .8rem;
        flex-direction: column;
        gap: 8px;
    }

    .chart-placeholder svg {
        width: 32px;
        height: 32px;
        opacity: .3;
    }

    /* ── Table détails chiffrés ── */
    .details-table-wrap {
        margin-top: 14px;
        border-top: 1px solid var(--card-border);
        padding-top: 12px;
    }

    .details-label {
        font-size: .7rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .details-label svg {
        width: 13px;
        height: 13px;
    }

    .dt {
        width: 100%;
        border-collapse: collapse;
        font-size: .78rem;
    }

    .dt th {
        color: var(--text-muted);
        font-size: .67rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 5px 8px;
        border-bottom: 1px solid var(--card-border);
        font-weight: 600;
        text-align: left;
    }

    .dt td {
        padding: 6px 8px;
        border-bottom: 1px solid rgba(255, 255, 255, .04);
        color: var(--text-main);
        vertical-align: middle;
    }

    .dt tbody tr:last-child td {
        border-bottom: none;
    }

    .dt tbody tr.total-row td {
        font-weight: 700;
        color: var(--text-main);
        border-top: 1px solid var(--card-border);
        background: rgba(255, 255, 255, .02);
    }

    .badge-pct {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 20px;
        font-size: .65rem;
        font-weight: 700;
        background: rgba(34, 201, 124, .12);
        color: var(--accent-green);
    }

    /* ── Statut badges colors ── */
    .sf-REJETER { background: rgba(240,82,82,.12); color: var(--accent-red); }
    .sf-NON_CONTROLE { background: rgba(155,109,255,.12); color: var(--accent-violet); }
    .sf-RECU { background: rgba(59,158,255,.12); color: var(--accent-blue); }
    .sf-EN_COURS {
        background: rgba(245, 166, 35, .12);
        color: #f5a623;
    }

    .sf-PAYE {
        background: rgba(34, 201, 124, .12);
        color: var(--accent-green);
    }

    .ov-TRAIT {
        background: rgba(59, 158, 255, .12);
        color: var(--accent-blue);
    }

    .ov-ATF {
        background: rgba(245, 166, 35, .12);
        color: #f5a623;
    }

    .ov-ADB_ATF {
        background: rgba(240, 82, 82, .12);
        color: var(--accent-red);
    }

    /* ── Section Lead Time (plein largeur) ── */
    .leadtime-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-md);
        overflow: hidden;
        margin-bottom: 18px;
    }

    .lt-bar-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }

    .lt-bar-label {
        font-size: .75rem;
        color: var(--text-secondary);
        min-width: 130px;
        text-align: right;
    }

    .lt-bar-track {
        flex: 1;
        height: 28px;
        background: rgba(255, 255, 255, .04);
        border-radius: 4px;
        position: relative;
        overflow: hidden;
    }

    .lt-bar-fill {
        height: 100%;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 8px;
        font-size: .72rem;
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        transition: width .6s ease;
    }

    .lt-bar-fill.PAYE {
        background: linear-gradient(90deg, #1a9e55, #22c97c);
    }

    .lt-bar-fill.REJETER {
        background: linear-gradient(90deg, #c40000, #f05252);
    }
    .lt-bar-fill.EN_COURS {
        background: linear-gradient(90deg, #c47d00, #f5a623);
    }

    .lt-val {
        font-size: .75rem;
        font-weight: 700;
        color: var(--accent-green);
        min-width: 50px;
    }

    .lt-count {
        font-size: .68rem;
        color: var(--text-muted);
    }

    /* ── Modal ── */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .7);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-box {
        background: #1a1d27;
        border: 1px solid var(--card-border);
        border-radius: var(--radius-lg);
        width: min(700px, 95vw);
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .6);
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-bottom: 1px solid var(--card-border);
        background: rgba(155, 109, 255, .08);
    }

    .modal-title {
        font-size: .88rem;
        font-weight: 700;
        color: var(--text-main);
        font-family: 'Space Grotesk', sans-serif;
    }

    .modal-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .btn-modal-excel {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        background: rgba(34, 201, 124, .1);
        color: var(--accent-green);
        border: 1px solid rgba(34, 201, 124, .2);
        border-radius: var(--radius-sm);
        font-size: .73rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-modal-img {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        background: rgba(59, 158, 255, .1);
        color: var(--accent-blue);
        border: 1px solid rgba(59, 158, 255, .2);
        border-radius: var(--radius-sm);
        font-size: .73rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-modal-close {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(240, 82, 82, .1);
        color: var(--accent-red);
        border: 1px solid rgba(240, 82, 82, .2);
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 1rem;
        font-weight: 700;
    }

    .modal-body {
        padding: 18px;
        overflow-y: auto;
        flex: 1;
    }

    .modal-chart-wrap {
        height: 280px;
        margin-bottom: 16px;
    }

    .btn-fermer {
        display: block;
        margin: 14px auto 0;
        padding: 8px 22px;
        background: rgba(255, 255, 255, .07);
        color: var(--text-secondary);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-sm);
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-fermer:hover {
        background: rgba(255, 255, 255, .12);
        color: var(--text-main);
    }

    /* ── Table dans modal ── */
    .modal-dt {
        width: 100%;
        border-collapse: collapse;
        font-size: .76rem;
    }

    .modal-dt th {
        padding: 5px 8px;
        border-bottom: 2px solid var(--card-border);
        font-size: .67rem;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: .4px;
        font-weight: 600;
        text-align: left;
    }

    .modal-dt td {
        padding: 6px 8px;
        border-bottom: 1px solid rgba(255, 255, 255, .04);
        color: var(--text-main);
    }

    .modal-dt tr:last-child td {
        border-bottom: none;
        font-weight: 700;
        border-top: 1px solid var(--card-border);
    }

    .col-header-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: .63rem;
        font-weight: 700;
        white-space: nowrap;
    }

    /* DataTables dark minimal */
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background: var(--main-bg) !important;
        border: 1px solid var(--card-border) !important;
        color: var(--text-main) !important;
        border-radius: var(--radius-sm) !important;
        padding: 4px 8px !important;
        font-size: .78rem !important;
        outline: none !important;
    }

    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_info {
        color: var(--text-muted) !important;
        font-size: .74rem;
    }

    .dataTables_wrapper .paginate_button {
        background: var(--card-bg) !important;
        border: 1px solid var(--card-border) !important;
        color: var(--text-secondary) !important;
        border-radius: var(--radius-sm) !important;
        padding: 3px 10px !important;
        font-size: .75rem !important;
        cursor: pointer;
    }

    .dataTables_wrapper .paginate_button.current,
    .dataTables_wrapper .paginate_button:hover {
        background: var(--accent-violet) !important;
        color: #fff !important;
        border-color: var(--accent-violet) !important;
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
            <span>Situation Graphique</span>
        </div>

        <!-- ══ Filtres ══ -->
        <div class="filter-block">
            <div class="filter-block-header">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z" />
                </svg>
                Critères d'analyse
            </div>
            <div class="filter-body">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label class="filter-label">Mouvements depuis le</label>
                        <input type="date" id="inp_date_debut" class="filter-ctrl" placeholder="jj/mm/aaaa">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Mouvements jusqu'au</label>
                        <input type="date" id="inp_date_fin" class="filter-ctrl" placeholder="jj/mm/aaaa">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Structure</label>
                        <select id="sel_structure" class="filter-ctrl">
                            <option value="">Toutes</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Statut</label>
                        <select id="sel_statut" class="filter-ctrl">
                            <option value="">Tous</option>
                            <option value="EN COURS">EN Cours de Traitement</option>
                            <option value="PAYE">Payée</option>
                            <option value="TRAIT">Traitement en cours</option>
                            <option value="ATF">En attente ATF</option>
                            <option value="ADB_ATF">En attente TDB +ATF</option>
                            <option value="REJETER">Rejetée</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Gestionnaire</label>
                        <select id="sel_gestionnaire" class="filter-ctrl">
                            <option value="">Tous</option>
                        </select>
                    </div>
                    <div class="filter-item" style="justify-content:flex-end;">
                        <button class="btn-reset" id="btn_reset">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z" />
                            </svg>
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ Graphiques principaux (2 colonnes) ══ -->
        <div class="graphs-row">

            <!-- Performance Gestionnaires -->
            <div class="graph-card">
                <div class="graph-card-header">
                    <div class="graph-card-title">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                        </svg>
                        Performance Gestionnaires
                    </div>
                    <div class="graph-card-actions">
                        <button class="btn-expand" id="btn_expand_perf" title="Voir détails">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 11V3h-8l3.29 3.29-10 10L3 13v8h8l-3.29-3.29 10-10z" />
                            </svg>
                        </button>
                        <button class="btn-charger" id="btn_load_perf">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" />
                            </svg>
                            Charger
                        </button>
                    </div>
                </div>
                <div class="graph-body">
                    <div id="perf_subtitle" class="graph-subtitle" style="display:none;"></div>
                    <div class="chart-container" id="perf_chart_wrap">
                        <div class="chart-placeholder">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z" />
                            </svg>
                            Cliquez sur Charger
                        </div>
                    </div>
                    <div class="details-table-wrap" id="perf_details" style="display:none;">
                        <div class="details-label">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                            </svg>
                            Détails Chiffrés
                        </div>
                        <div id="perf_table_container"></div>
                    </div>
                </div>
            </div>

            <!-- Répartition Régionale -->
            <div class="graph-card">
                <div class="graph-card-header">
                    <div class="graph-card-title">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                        </svg>
                        Répartition Régionale
                    </div>
                    <div class="graph-card-actions">
                        <button class="btn-expand" id="btn_expand_reg" title="Voir détails">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 11V3h-8l3.29 3.29-10 10L3 13v8h8l-3.29-3.29 10-10z" />
                            </svg>
                        </button>
                        <button class="btn-charger" id="btn_load_reg">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" />
                            </svg>
                            Charger
                        </button>
                    </div>
                </div>
                <div class="graph-body">
                    <div id="reg_subtitle" class="graph-subtitle" style="display:none;"></div>
                    <div class="chart-container" id="reg_chart_wrap">
                        <div class="chart-placeholder">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                            </svg>
                            Cliquez sur Charger
                        </div>
                    </div>
                    <div class="details-table-wrap" id="reg_details" style="display:none;">
                        <div class="details-label">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                            </svg>
                            Détails Chiffrés
                        </div>
                        <div id="reg_table_container"></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ══ Lead Time (plein largeur) ══ -->
        <div class="leadtime-card">
            <div class="graph-card-header">
                <div class="graph-card-title">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm.5 5v5.25l4.5 2.67-.75 1.23L11 13V7h1.5z" />
                    </svg>
                    Temps Moyen de Traitement (Lead Time)
                </div>
                <div class="graph-card-actions">
                    <button class="btn-expand" id="btn_expand_lt" title="Voir détails">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 11V3h-8l3.29 3.29-10 10L3 13v8h8l-3.29-3.29 10-10z" />
                        </svg>
                    </button>
                    <button class="btn-charger violet" id="btn_load_lt">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" />
                        </svg>
                        Charger
                    </button>
                </div>
            </div>
            <div class="graph-body" id="lt_body">
                <div class="chart-placeholder">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm.5 5v5.25l4.5 2.67-.75 1.23L11 13V7h1.5z" />
                    </svg>
                    Cliquez sur Charger
                </div>
            </div>
        </div>

        <?php require_once '../Includes/footer.php'; ?>
    </div>
</div>

<!-- ══ Modal Détails ══ -->
<div class="modal-overlay" id="modal_overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="modal_title">Détails</div>
            <div class="modal-actions">
                <button class="btn-modal-img" id="modal_btn_img">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" />
                    </svg>
                    IMG
                </button>
                <button class="btn-modal-excel" id="modal_btn_excel">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                    </svg>
                    Excel
                </button>
                <div class="btn-modal-close" id="modal_close">×</div>
            </div>
        </div>
        <div class="modal-body">
            <div class="modal-chart-wrap"><canvas id="modal_chart"></canvas></div>
            <div style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;display:flex;align-items:center;gap:5px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                </svg>
                Données Détaillées
            </div>
            <div id="modal_table_wrap"></div>
        </div>
        <button class="btn-fermer" id="modal_fermer">Fermer</button>
    </div>
</div>

<script>
    $(document).ready(function() {
        const API = '<?= linkTo('Controllers/LOCAL_API/Dashboard/dashboard_api.php') ?>';

        // ── Couleurs statuts ──────────────────────────────────────────────────
        const STATUT_COLORS = {
            'EN COURS': '#f5a623',
            REJETER: '#f05252',
            NON_CONTROLE: '#9b6dff',
            RECU: '#3b9eff',
            PAYE: '#22c97c',
            TRAIT: '#3b9eff',
            ATF: '#ffd166',
            ADB_ATF: '#f05252',
        };
        const STATUT_LABELS = {
            'EN COURS': 'EN Cours de Traitement',
            REJETER: 'Rejetée',
            NON_CONTROLE: 'Créée par Structure',
            RECU: 'Réceptionnée',
            PAYE: 'Payée',
            TRAIT: 'Traitement en cours',
            ATF: 'En attente ATF',
            ADB_ATF: 'En attente TDB +ATF',
        };

        // ── Chart instances ───────────────────────────────────────────────────
        let chartPerf = null,
            chartReg = null,
            chartModal = null;

        // ── Données en mémoire pour modal/expand ─────────────────────────────
        let lastPerfData = null,
            lastRegData = null,
            lastLtData = null;
        let modalMode = ''; // 'perf' | 'reg' | 'lt'

        // ── Load selects ──────────────────────────────────────────────────────
        fetch(API, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get_structures'
                })
            })
            .then(r => r.json()).then(res => {
                if (!res.success) return;
                const sel = document.getElementById('sel_structure');
                res.data.forEach(s => sel.innerHTML += `<option value="${s.id}">${s.label}</option>`);
            });
        fetch(API, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get_gestionnaires'
                })
            })
            .then(r => r.json()).then(res => {
                if (!res.success) return;
                const sel = document.getElementById('sel_gestionnaire');
                res.data.forEach(g => sel.innerHTML += `<option value="${g.id}">${g.nom_complet}</option>`);
            });

        // ── Helpers ───────────────────────────────────────────────────────────
        function getFilters() {
            return {
                date_debut: $('#inp_date_debut').val(),
                date_fin: $('#inp_date_fin').val(),
                structure_id: $('#sel_structure').val(),
                statut_code: $('#sel_statut').val(),
                gestionnaire_id: $('#sel_gestionnaire').val(),
            };
        }

        function getStructureLabel() {
            const sel = document.getElementById('sel_structure');
            return sel.value ? sel.options[sel.selectedIndex].text : 'Toutes';
        }

        function getStatutLabel() {
            const sel = document.getElementById('sel_statut');
            return sel.value ? sel.options[sel.selectedIndex].text : 'Tous statuts';
        }

        function periodLabel() {
            const d = $('#inp_date_debut').val(),
                f = $('#inp_date_fin').val();
            if (!d && !f) return 'Tout l\'historique';
            if (d && f) return d + ' → ' + f;
            if (d) return 'Depuis ' + d;
            return 'Jusqu\'au ' + f;
        }

        // ── Destroy chart helper ──────────────────────────────────────────────
        function destroyChart(ch) {
            if (ch) {
                try {
                    ch.destroy();
                } catch (e) {}
            }
            return null;
        }

        // ════════════════════════════════════════════════════════════════════
        // ── Performance Gestionnaires ──────────────────────────────────────
        // ════════════════════════════════════════════════════════════════════
        $('#btn_load_perf').on('click', function() {
            const filters = getFilters();
            const btn = $(this).prop('disabled', true).text('Chargement…');

            fetch(API, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'graphique_performance',
                        ...filters
                    })
                })
                .then(r => r.json())
                .then(res => {
                    btn.prop('disabled', false).html(`<svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px;"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg> Charger`);
                    if (!res.success || !res.data.length) {
                        $('#perf_chart_wrap').html('<div class="chart-placeholder"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/></svg>Aucune donnée</div>');
                        return;
                    }
                    lastPerfData = res;
                    renderPerfChart(res, filters.statut_code);
                })
                .catch(() => {
                    btn.prop('disabled', false).text('Charger');
                });
        });

        function renderPerfChart(res, statutCode) {
            const data = res.data;
            const statuts = Object.keys(res.statuts);
            const labels = data.map(g => g.nom);
            const isSingleStatut = statutCode !== '';

            // Subtitle
            const sl = getStatutLabel(),
                stl = getStructureLabel(),
                pd = periodLabel();
            let sub = `Période : <strong>${pd}</strong><br>Filtre: <strong style="color:#22c97c;">${sl}</strong>`;
            if ($('#sel_structure').val()) sub += ` | Structure: <strong>${stl}</strong>`;
            $('#perf_subtitle').html(sub).show();

            // Destroy old chart
            chartPerf = destroyChart(chartPerf);
            const wrap = document.getElementById('perf_chart_wrap');
            wrap.innerHTML = '<canvas id="perf_canvas"></canvas>';
            wrap.querySelector('canvas').style.height = '260px';

            const ctx = document.getElementById('perf_canvas').getContext('2d');

            if (isSingleStatut) {
                // Bar chart simple (une couleur verte)
                const vals = data.map(g => g.total);
                chartPerf = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: getStatutLabel(),
                            data: vals,
                            backgroundColor: '#22c97c',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            datalabels: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.parsed.y + ' factures'
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 10
                                    }
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                }
                            },
                            y: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 10
                                    }
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                },
                                beginAtZero: true
                            }
                        },
                    }
                });
            } else {
                // Donut chart (tous statuts)
                const totaux = {};
                statuts.forEach(s => {
                    totaux[s] = 0;
                });
                data.forEach(g => {
                    statuts.forEach(s => {
                        totaux[s] += g.statuts[s] || 0;
                    });
                });
                const pieLabels = statuts.map(s => STATUT_LABELS[s] || s);
                const pieValues = statuts.map(s => totaux[s]);
                const total = pieValues.reduce((a, b) => a + b, 0);

                chartPerf = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: pieLabels,
                        datasets: [{
                            data: pieValues,
                            backgroundColor: statuts.map(s => STATUT_COLORS[s] || '#888'),
                            borderWidth: 2,
                            borderColor: '#1a1d27',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: '#e4e8f1',
                                    font: {
                                        size: 10
                                    },
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.label + ': ' + ctx.parsed + ' (' + (total ? (ctx.parsed / total * 100).toFixed(1) : 0) + '%)'
                                }
                            }
                        }
                    }
                });
            }

            // Table
            renderPerfTable(data, statuts, res.statuts, isSingleStatut);
            $('#perf_details').show();
        }

        function renderPerfTable(data, statuts, statutsLabels, isSingleStatut) {
            const total = data.reduce((a, g) => a + g.total, 0);
            let html = '';

            if (isSingleStatut) {
                html = `<table class="dt"><thead><tr><th>Gestionnaire</th><th>Volume</th><th>%</th></tr></thead><tbody>`;
                data.sort((a, b) => b.total - a.total).forEach(g => {
                    const pct = total ? (g.total / total * 100).toFixed(1) : 0;
                    html += `<tr><td>${g.nom}</td><td>${g.total}</td><td><span class="badge-pct">${pct}%</span></td></tr>`;
                });
                html += `<tr class="total-row"><td>TOTAL GÉNÉRAL</td><td>${total}</td><td>100%</td></tr></tbody></table>`;
            } else {
                const sTotal = {};
                statuts.forEach(s => {
                    sTotal[s] = data.reduce((a, g) => a + (g.statuts[s] || 0), 0);
                });
                const headers = statuts.map(s => `<th><span class="col-header-badge" style="background:${STATUT_COLORS[s]}22;color:${STATUT_COLORS[s]};">${statutsLabels[s]||s}</span></th>`).join('');
                html = `<table class="dt"><thead><tr><th>Gestionnaire</th>${headers}<th>TOTAL</th></tr></thead><tbody>`;
                data.forEach(g => {
                    const cells = statuts.map(s => `<td>${g.statuts[s] ? g.statuts[s] : '<span style="color:var(--text-muted)">-</span>'}</td>`).join('');
                    html += `<tr><td>${g.nom}</td>${cells}<td><strong>${g.total}</strong></td></tr>`;
                });
                const footCells = statuts.map(s => `<td>${sTotal[s]}</td>`).join('');
                html += `<tr class="total-row"><td>TOTAL GÉNÉRAL</td>${footCells}<td><strong>${total}</strong></td></tr></tbody></table>`;
            }
            document.getElementById('perf_table_container').innerHTML = html;
        }

        // ════════════════════════════════════════════════════════════════════
        // ── Répartition Régionale ──────────────────────────────────────────
        // ════════════════════════════════════════════════════════════════════
        $('#btn_load_reg').on('click', function() {
            const filters = getFilters();
            const btn = $(this).prop('disabled', true).text('Chargement…');

            fetch(API, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'graphique_regional',
                        ...filters
                    })
                })
                .then(r => r.json())
                .then(res => {
                    btn.prop('disabled', false).html(`<svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px;"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg> Charger`);
                    if (!res.success || !res.data.length) {
                        $('#reg_chart_wrap').html('<div class="chart-placeholder"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>Aucune donnée</div>');
                        return;
                    }
                    lastRegData = res;
                    renderRegChart(res, filters.statut_code, filters.structure_id);
                })
                .catch(() => {
                    btn.prop('disabled', false).text('Charger');
                });
        });

        function renderRegChart(res, statutCode, structureId) {
            const data = res.data;
            const statuts = Object.keys(res.statuts);
            const isSingleStatut = statutCode !== '';
            const isSingleStruct = structureId !== '';

            const sl = getStatutLabel(),
                stl = getStructureLabel(),
                pd = periodLabel();
            let sub = `Période : <strong>${pd}</strong><br>Filtre: <strong style="color:#22c97c;">${sl}</strong>`;
            if (isSingleStruct) sub += ` | Focus Structure: <strong>${stl}</strong>`;
            $('#reg_subtitle').html(sub).show();

            chartReg = destroyChart(chartReg);
            const wrap = document.getElementById('reg_chart_wrap');
            wrap.innerHTML = '<canvas id="reg_canvas"></canvas>';
            wrap.querySelector('canvas').style.height = '260px';
            const ctx = document.getElementById('reg_canvas').getContext('2d');

            const labels = data.map(s => s.nom);
            const total = data.reduce((a, s) => a + s.total, 0);

            if (isSingleStruct) {
                // Une seule structure → 1 grosse barre verte
                chartReg = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: sl,
                            data: data.map(s => s.total),
                            backgroundColor: '#22c97c',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.parsed.y + ' factures'
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 10
                                    }
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                }
                            },
                            y: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 10
                                    }
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else if (isSingleStatut) {
                // Un statut, plusieurs structures → barres vertes
                chartReg = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: sl,
                            data: data.map(s => s.total),
                            backgroundColor: '#22c97c',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    color: '#e4e8f1',
                                    font: {
                                        size: 10
                                    },
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.parsed.y + ' factures'
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 9
                                    },
                                    maxRotation: 30
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                }
                            },
                            y: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 10
                                    }
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                // Tous statuts, toutes structures → barres séparées (non empilées)
                const datasets = statuts.map(s => ({
                    label: STATUT_LABELS[s] || s,
                    data: data.map(d => d.statuts[s] || 0),
                    backgroundColor: STATUT_COLORS[s] || '#888',
                    borderRadius: 2,
                }));
                chartReg = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#e4e8f1',
                                    font: {
                                        size: 9
                                    },
                                    boxWidth: 10
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 9
                                    },
                                    maxRotation: 30
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                }
                            },
                            y: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 10
                                    }
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Table
            renderRegTable(data, statuts, res.statuts, isSingleStatut);
            $('#reg_details').show();
        }

        function renderRegTable(data, statuts, statutsLabels, isSingleStatut) {
            const total = data.reduce((a, s) => a + s.total, 0);
            let html = '';
            if (isSingleStatut) {
                html = `<table class="dt"><thead><tr><th>Structure</th><th>Volume</th><th>%</th></tr></thead><tbody>`;
                data.sort((a, b) => b.total - a.total).forEach(s => {
                    const pct = total ? (s.total / total * 100).toFixed(1) : 0;
                    html += `<tr><td>${s.nom}</td><td>${s.total}</td><td><span class="badge-pct">${pct}%</span></td></tr>`;
                });
                html += `<tr class="total-row"><td>TOTAL GÉNÉRAL</td><td>${total}</td><td>100%</td></tr></tbody></table>`;
            } else {
                const sTotal = {};
                statuts.forEach(s => {
                    sTotal[s] = data.reduce((a, d) => a + (d.statuts[s] || 0), 0);
                });
                const headers = statuts.map(s => `<th><span class="col-header-badge" style="background:${STATUT_COLORS[s]}22;color:${STATUT_COLORS[s]};">${statutsLabels[s]||s}</span></th>`).join('');
                html = `<table class="dt"><thead><tr><th>Structure</th>${headers}<th>TOTAL</th></tr></thead><tbody>`;
                data.sort((a, b) => b.total - a.total).forEach(d => {
                    const cells = statuts.map(s => `<td>${d.statuts[s] || '<span style="color:var(--text-muted)">-</span>'}</td>`).join('');
                    html += `<tr><td>${d.nom}</td>${cells}<td><strong>${d.total}</strong></td></tr>`;
                });
                const footCells = statuts.map(s => `<td>${sTotal[s]}</td>`).join('');
                html += `<tr class="total-row"><td>TOTAL GÉNÉRAL</td>${footCells}<td><strong>${total}</strong></td></tr></tbody></table>`;
            }
            document.getElementById('reg_table_container').innerHTML = html;
        }

        // ════════════════════════════════════════════════════════════════════
        // ── Lead Time ─────────────────────────────────────────────────────
        // ════════════════════════════════════════════════════════════════════
        $('#btn_load_lt').on('click', function() {
            const filters = getFilters();
            const btn = $(this).prop('disabled', true).text('Chargement…');

            fetch(API, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'graphique_leadtime',
                        ...filters
                    })
                })
                .then(r => r.json())
                .then(res => {
                    btn.prop('disabled', false).html(`<svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px;"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg> Charger`);
                    if (!res.success || !res.data.length) {
                        $('#lt_body').html('<div class="chart-placeholder"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm.5 5v5.25l4.5 2.67-.75 1.23L11 13V7h1.5z"/></svg>Aucune donnée</div>');
                        return;
                    }
                    lastLtData = res;
                    renderLeadTime(res, getFilters().statut_code);
                })
                .catch(() => {
                    btn.prop('disabled', false).text('Charger');
                });
        });

        function renderLeadTime(res, statutCode) {
            const data = res.data;
            const maxVal = Math.max(...data.map(g => g.moyenne_globale), 1);
            const pd = periodLabel(),
                sl = getStatutLabel();
            const stCode = (statutCode || 'PAYE').replace(/ /g, '_');

            let html = `<div style="font-size:.72rem;text-align:center;color:var(--text-muted);margin-bottom:14px;">
            Période : <strong style="color:var(--text-secondary);">${pd}</strong><br>
            Analyse des Temps de Traitement (Lead Time) par Gestionnaire | Filtre Statut: <strong style="color:#22c97c;">${sl || 'PAYÉE'}</strong>
        </div>
        <div style="margin-bottom:6px;display:flex;align-items:center;gap:6px;font-size:.7rem;color:var(--text-muted);">
            <span style="width:28px;height:10px;background:#22c97c;border-radius:2px;display:inline-block;"></span>
            ${sl || 'PAYÉE'}
        </div>`;

            data.forEach(g => {
                const pct = maxVal ? (g.moyenne_globale / maxVal * 100) : 0;
                html += `<div class="lt-bar-row">
                <div class="lt-bar-label">${g.nom}</div>
                <div class="lt-bar-track">
                    <div class="lt-bar-fill ${stCode}" style="width:${pct}%;">
                        <span style="font-size:.67rem;">${sl || 'PAYÉE'}</span>
                    </div>
                </div>
                <div class="lt-val">${g.moyenne_globale} jrs</div>
            </div>`;
            });

            // Table détails par statut
            html += `<div class="details-table-wrap" style="margin-top:18px;">
            <div class="details-label" style="display:flex;align-items:center;gap:5px;font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px;margin-bottom:8px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                Détails par Statut (Moyenne Jours)
            </div>
            <table class="dt"><thead><tr><th>Gestionnaire</th>`;

            const allStatuts = new Set();
            data.forEach(g => Object.keys(g.statuts).forEach(s => allStatuts.add(s)));
            const statList = Array.from(allStatuts);
            statList.forEach(s => {
                html += `<th><span class="col-header-badge" style="background:${STATUT_COLORS[s]}22;color:${STATUT_COLORS[s]};">${STATUT_LABELS[s]||s}</span></th>`;
            });
            html += '</tr></thead><tbody>';
            data.forEach(g => {
                html += `<tr><td>${g.nom}</td>`;
                statList.forEach(s => {
                    const st = g.statuts[s];
                    html += st ? `<td><strong style="color:#22c97c;">${st.moyenne_jours} jrs</strong><br><span class="lt-count">(${st.nb_factures} Factures.)</span></td>` : '<td>—</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table></div>';

            document.getElementById('lt_body').innerHTML = html;
        }

        // ════════════════════════════════════════════════════════════════════
        // ── Modal (expand) ─────────────────────────────────────────────────
        // ════════════════════════════════════════════════════════════════════
        function openModal(mode) {
            modalMode = mode;
            const overlay = document.getElementById('modal_overlay');
            overlay.classList.add('active');
            chartModal = destroyChart(chartModal);

            const ctx = document.getElementById('modal_chart').getContext('2d');

            if (mode === 'perf' && lastPerfData) {
                document.getElementById('modal_title').textContent = 'Détail : Performance Gestionnaire (Volumes)';
                const res = lastPerfData;
                const statuts = Object.keys(res.statuts);
                const statutCode = getFilters().statut_code;
                const isSingle = statutCode !== '';
                const data = res.data;

                if (isSingle) {
                    chartModal = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.map(g => g.nom),
                            datasets: [{
                                label: getStatutLabel(),
                                data: data.map(g => g.total),
                                backgroundColor: '#22c97c',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#888',
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                y: {
                                    ticks: {
                                        color: '#888'
                                    },
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                } else {
                    const totaux = {};
                    statuts.forEach(s => {
                        totaux[s] = data.reduce((a, g) => a + (g.statuts[s] || 0), 0);
                    });
                    const total = Object.values(totaux).reduce((a, b) => a + b, 0);
                    chartModal = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: statuts.map(s => STATUT_LABELS[s] || s),
                            datasets: [{
                                data: statuts.map(s => totaux[s]),
                                backgroundColor: statuts.map(s => STATUT_COLORS[s] || '#888'),
                                borderWidth: 2,
                                borderColor: '#1a1d27'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: '#e4e8f1',
                                        font: {
                                            size: 11
                                        },
                                        boxWidth: 12
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => ctx.label + ': ' + ctx.parsed + ' (' + ((ctx.parsed / total) * 100).toFixed(1) + '%)'
                                    }
                                }
                            }
                        }
                    });
                }

                // Table in modal
                const isSingleStatut = getFilters().statut_code !== '';
                document.getElementById('modal_table_wrap').innerHTML = document.getElementById('perf_table_container').innerHTML;

            } else if (mode === 'reg' && lastRegData) {
                document.getElementById('modal_title').textContent = 'Détail : Répartition Régionale (Volumes)';
                const res = lastRegData;
                const statuts = Object.keys(res.statuts);
                const isSingleStatut = getFilters().statut_code !== '';
                const isSingleStruct = getFilters().structure_id !== '';
                const data = res.data;

                if (isSingleStruct || isSingleStatut) {
                    chartModal = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.map(s => s.nom),
                            datasets: [{
                                label: getStatutLabel(),
                                data: data.map(s => s.total),
                                backgroundColor: '#22c97c',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#888',
                                        font: {
                                            size: 10
                                        },
                                        maxRotation: 30
                                    }
                                },
                                y: {
                                    ticks: {
                                        color: '#888'
                                    },
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                } else {
                    const datasets = statuts.map(s => ({
                        label: STATUT_LABELS[s] || s,
                        data: data.map(d => d.statuts[s] || 0),
                        backgroundColor: STATUT_COLORS[s] || '#888',
                        borderRadius: 2
                    }));
                    chartModal = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.map(s => s.nom),
                            datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        color: '#e4e8f1',
                                        font: {
                                            size: 9
                                        },
                                        boxWidth: 10
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#888',
                                        font: {
                                            size: 9
                                        },
                                        maxRotation: 30
                                    }
                                },
                                y: {
                                    ticks: {
                                        color: '#888'
                                    },
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
                document.getElementById('modal_table_wrap').innerHTML = document.getElementById('reg_table_container').innerHTML;

            } else if (mode === 'lt' && lastLtData) {
                document.getElementById('modal_title').textContent = 'Détail : Temps de Traitement (Moyenne en Jours)';
                const res = lastLtData;
                const statCode = (getFilters().statut_code || 'PAYE').replace(/ /g, '_');
                const data = res.data;
                chartModal = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(g => g.nom),
                        datasets: [{
                            label: getStatutLabel() || 'Payée',
                            data: data.map(g => g.moyenne_globale),
                            backgroundColor: '#22c97c',
                            borderRadius: 4,
                            indexAxis: 'y'
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.parsed.x + ' jours'
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#888'
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.05)'
                                },
                                beginAtZero: true
                            },
                            y: {
                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                });
                // Table
                let th = `<table class="modal-dt"><thead><tr><th>Gestionnaire</th><th><span class="col-header-badge" style="background:#22c97c22;color:#22c97c;">${getStatutLabel()||'Payée'}</span></th></tr></thead><tbody>`;
                data.forEach(g => {
                    th += `<tr><td>${g.nom}</td><td><strong style="color:#22c97c;">${g.moyenne_globale} jrs</strong><br><span class="lt-count">(${g.total_factures} Factures.)</span></td></tr>`;
                });
                th += `<tr><td>TOTAL GÉNÉRAL</td><td>TBD</td></tr></tbody></table>`;
                document.getElementById('modal_table_wrap').innerHTML = th;
            }
        }

        $('#btn_expand_perf').on('click', () => {
            if (lastPerfData) openModal('perf');
        });
        $('#btn_expand_reg').on('click', () => {
            if (lastRegData) openModal('reg');
        });
        $('#btn_expand_lt').on('click', () => {
            if (lastLtData) openModal('lt');
        });

        // Close modal
        function closeModal() {
            document.getElementById('modal_overlay').classList.remove('active');
            chartModal = destroyChart(chartModal);
        }
        $('#modal_close, #modal_fermer').on('click', closeModal);
        $('#modal_overlay').on('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Modal Excel
        $('#modal_btn_excel').on('click', function() {
            const table = document.querySelector('#modal_table_wrap table');
            if (!table) return;
            let csv = [];
            table.querySelectorAll('tr').forEach(row => {
                const cols = [];
                row.querySelectorAll('th,td').forEach(cell => cols.push('"' + cell.innerText.replace(/"/g, '""') + '"'));
                csv.push(cols.join(','));
            });
            const blob = new Blob(['\uFEFF' + csv.join('\n')], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'graphique_' + modalMode + '_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
        });

        // Modal IMG
        $('#modal_btn_img').on('click', function() {
            const canvas = document.getElementById('modal_chart');
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = 'graphique_' + modalMode + '_' + new Date().toISOString().slice(0, 10) + '.png';
            link.click();
        });

        // ── Reset ─────────────────────────────────────────────────────────────
        $('#btn_reset').on('click', function() {
            $('#inp_date_debut, #inp_date_fin').val('');
            $('#sel_structure, #sel_statut, #sel_gestionnaire').val('');
            lastPerfData = lastRegData = lastLtData = null;
            chartPerf = destroyChart(chartPerf);
            chartReg = destroyChart(chartReg);
            ['perf_chart_wrap', 'reg_chart_wrap'].forEach(id => {
                document.getElementById(id).innerHTML = '<div class="chart-placeholder"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/></svg>Cliquez sur Charger</div>';
            });
            $('#perf_details, #reg_details, #perf_subtitle, #reg_subtitle').hide();
            document.getElementById('lt_body').innerHTML = '<div class="chart-placeholder"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm.5 5v5.25l4.5 2.67-.75 1.23L11 13V7h1.5z"/></svg>Cliquez sur Charger</div>';
        });
    });
</script>
