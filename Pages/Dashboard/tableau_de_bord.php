<?php
session_start();
$page_title = "Tableau de Bord";

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

    /* ── Stat cards row ── */
    .db-stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 28px;
    }

    @media (max-width: 1100px) {
        .db-stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .db-stats-row {
            grid-template-columns: 1fr;
        }
    }

    .db-stat-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-lg);
        padding: 22px 22px 18px;
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        cursor: default;
    }

    .db-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
        background: var(--card-bg-hover);
    }

    .db-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .db-stat-card.teal::before {
        background: linear-gradient(90deg, #00c9a7, #00f5c4);
    }

    .db-stat-card.green::before {
        background: linear-gradient(90deg, var(--accent-green), #6effba);
    }

    .db-stat-card.amber::before {
        background: linear-gradient(90deg, #f5a623, #ffd166);
    }

    .db-stat-card.red::before {
        background: linear-gradient(90deg, var(--accent-red), #ff8c8c);
    }

    .db-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .db-stat-label {
        font-size: 0.73rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .db-stat-icon {
        width: 38px;
        height: 38px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .db-stat-icon svg {
        width: 20px;
        height: 20px;
    }

    .db-stat-card.teal .db-stat-icon {
        background: rgba(0, 201, 167, .12);
        color: #00c9a7;
    }

    .db-stat-card.green .db-stat-icon {
        background: rgba(34, 201, 124, .12);
        color: var(--accent-green);
    }

    .db-stat-card.amber .db-stat-icon {
        background: rgba(245, 166, 35, .12);
        color: #f5a623;
    }

    .db-stat-card.red .db-stat-icon {
        background: rgba(240, 82, 82, .12);
        color: var(--accent-red);
    }

    .db-stat-value {
        font-size: 1.85rem;
        font-weight: 700;
        font-family: 'Space Grotesk', sans-serif;
        color: var(--text-main);
        line-height: 1;
        margin-bottom: 6px;
    }

    .db-stat-sub {
        font-size: 0.72rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .db-stat-more {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 14px;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--accent-blue);
        opacity: .8;
        transition: var(--transition);
        text-decoration: none;
    }

    .db-stat-more:hover {
        opacity: 1;
        color: var(--accent-blue);
    }

    .db-stat-more svg {
        width: 12px;
        height: 12px;
    }

    /* ── Section titre ── */
    .db-section-title {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-muted);
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--card-border);
    }

    /* ── Report cards ── */
    .db-reports-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }

    @media (max-width: 900px) {
        .db-reports-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 560px) {
        .db-reports-grid {
            grid-template-columns: 1fr;
        }
    }

    .db-report-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-md);
        padding: 22px 20px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .db-report-card:hover {
        background: var(--card-bg-hover);
        border-color: rgba(59, 158, 255, .25);
        transform: translateY(-2px);
        box-shadow: var(--shadow-card);
    }

    .db-report-card-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 4px;
    }

    .db-report-card-icon svg {
        width: 22px;
        height: 22px;
    }

    .icon-blue {
        background: rgba(59, 158, 255, .12);
        color: var(--accent-blue);
    }

    .icon-amber {
        background: rgba(245, 166, 35, .12);
        color: #f5a623;
    }

    .icon-green {
        background: rgba(34, 201, 124, .12);
        color: var(--accent-green);
    }

    .icon-soon {
        background: rgba(255, 255, 255, .05);
        color: var(--text-muted);
    }

    .icon-violet {
        background: rgba(155, 109, 255, .12);
        color: var(--accent-violet);
    }

    .db-report-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-main);
        font-family: 'Space Grotesk', sans-serif;
    }

    .db-report-desc {
        font-size: 0.78rem;
        color: var(--text-muted);
        line-height: 1.55;
        flex: 1;
    }

    .btn-rapport-blue {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        background: var(--accent-blue);
        color: #fff;
        text-decoration: none;
        transition: var(--transition);
        align-self: flex-start;
        margin-top: 4px;
    }

    .btn-rapport-blue:hover {
        background: var(--accent-blue-2);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(59, 158, 255, .35);
    }

    .btn-rapport-amber {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        background: #f5a623;
        color: #000;
        text-decoration: none;
        transition: var(--transition);
        align-self: flex-start;
        margin-top: 4px;
    }

    .btn-rapport-amber:hover {
        background: #ffd166;
        color: #000;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(245, 166, 35, .35);
    }

    .btn-rapport-violet {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        background: var(--accent-violet);
        color: #fff;
        text-decoration: none;
        transition: var(--transition);
        align-self: flex-start;
        margin-top: 4px;
    }

    .btn-rapport-violet:hover {
        background: #b98aff;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(155, 109, 255, .35);
    }

    .btn-bientot {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid var(--card-border);
        background: rgba(255, 255, 255, .04);
        color: var(--text-muted);
        cursor: not-allowed;
        align-self: flex-start;
        margin-top: 4px;
    }

    /* ── Skeleton loader ── */
    .skeleton {
        display: inline-block;
        width: 90px;
        height: 1.85rem;
        background: linear-gradient(90deg, var(--card-border) 25%, rgba(255, 255, 255, .05) 50%, var(--card-border) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.4s infinite;
        border-radius: 4px;
    }

    @keyframes shimmer {
        0% {
            background-position: 200% 0
        }

        100% {
            background-position: -200% 0
        }
    }
</style>

<div class="main-content">
    <?php require_once '../Includes/topbar.php'; ?>

    <div class="content-area">

        <!-- Breadcrumb -->
        <div class="breadcrumb-bar">
            <a href="#">Accueil</a>
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" />
            </svg>
            <span>Tableau de Bord</span>
        </div>

        <!-- ══ Cartes statistiques ══ -->
        <div class="db-stats-row">

            <!-- Factures Enregistrées -->
            <div class="db-stat-card teal">
                <div class="db-stat-top">
                    <div class="db-stat-label">Factures Enregistrées</div>
                    <div class="db-stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                        </svg>
                    </div>
                </div>
                <div class="db-stat-value" id="stat-total-factures"><span class="skeleton"></span></div>
                <div class="db-stat-sub">Total factures dans le système</div>
                <a href="#" class="db-stat-more">
                    Plus d'infos
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                    </svg>
                </a>
            </div>

            <!-- Chiffre d'Affaires -->
            <div class="db-stat-card green">
                <div class="db-stat-top">
                    <div class="db-stat-label">Chiffre d'Affaires Total</div>
                    <div class="db-stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                        </svg>
                    </div>
                </div>
                <div class="db-stat-value" id="stat-ca" style="font-size:1.25rem;"><span class="skeleton" style="width:140px;"></span></div>
                <div class="db-stat-sub" id="stat-ca-sub">DZD — Montant total factures</div>
                <a href="#" class="db-stat-more">
                    Plus d'infos
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                    </svg>
                </a>
            </div>

            <!-- Fournisseurs Actifs -->
            <div class="db-stat-card amber">
                <div class="db-stat-top">
                    <div class="db-stat-label">Fournisseurs Actifs</div>
                    <div class="db-stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10z" />
                        </svg>
                    </div>
                </div>
                <div class="db-stat-value" id="stat-fournisseurs"><span class="skeleton"></span></div>
                <div class="db-stat-sub">Fournisseurs ayant un contrat</div>
                <a href="#" class="db-stat-more">
                    Plus d'infos
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                    </svg>
                </a>
            </div>

            <!-- Contrats en Cours -->
            <div class="db-stat-card red">
                <div class="db-stat-top">
                    <div class="db-stat-label">Contrats en Cours</div>
                    <div class="db-stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H7v-2h5v2zm5-4H7v-2h10v2zm0-4H7V7h10v2z" />
                        </svg>
                    </div>
                </div>
                <div class="db-stat-value" id="stat-contrats"><span class="skeleton"></span></div>
                <div class="db-stat-sub">Total contrats enregistrés</div>
                <a href="#" class="db-stat-more">
                    Plus d'infos
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                    </svg>
                </a>
            </div>

        </div>

        <!-- ══ Rapports & Bilans ══ -->
        <div class="section-card">
            <div class="section-header" style="padding:16px 22px;border-bottom:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.8px;">Rapports et Bilans</span>
            </div>
            <div style="padding:22px;">
                <div class="db-reports-grid">

                    <!-- Rapport 001 : Chiffre d'Affaire Fournisseur -->
                    <div class="db-report-card">
                        <div class="db-report-card-icon icon-blue">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                            </svg>
                        </div>
                        <div class="db-report-title">Chiffre d'Affaire Fournisseur</div>
                        <div class="db-report-desc">
                            Analyser le chiffre d'affaires réalisé avec un ou plusieurs fournisseurs, filtré par contrat, structure, monnaie et statut.
                        </div>
                        <a href="<?= linkTo('Pages/Dashboard/rapport_fournisseur.php') ?>" class="btn-rapport-blue">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                            </svg>
                            Accéder au rapport
                        </a>
                    </div>

                    <!-- Rapport 002 : Suivi des Paiements OV -->
                    <div class="db-report-card">
                        <div class="db-report-card-icon icon-amber">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z" />
                            </svg>
                        </div>
                        <div class="db-report-title">Suivies des Paiement O.V</div>
                        <div class="db-report-desc">
                            Suivre les Ordres de virement en temps réel : fournisseur, contrat, structure, monnaie et statut OV.
                        </div>
                        <a href="<?= linkTo('Pages/Dashboard/recap_ordres_virement.php') ?>" class="btn-rapport-amber">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                            </svg>
                            Accéder au rapport
                        </a>
                    </div>

                    <!-- Situation Graphique -->
                    <div class="db-report-card">
                        <div class="db-report-card-icon icon-violet">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z" />
                            </svg>
                        </div>
                        <div class="db-report-title">Situation Graphique</div>
                        <div class="db-report-desc">
                            Visualiser la performance des gestionnaires, la répartition régionale et le temps moyen de traitement des factures.
                        </div>
                        <a href="<?= linkTo('Pages/Dashboard/rapport_graphique.php') ?>" class="btn-rapport-violet">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                            </svg>
                            Accéder au rapport
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <?php require_once '../Includes/footer.php'; ?>
    </div><!-- /content-area -->
</div><!-- /main-content -->

<script>
    (function() {
        const API = '<?= linkTo('Controllers/LOCAL_API/Dashboard/dashboard_api.php') ?>';

        function fmt(n) {
            if (n >= 1e12) return (n / 1e12).toFixed(2).replace(/\.?0+$/, '') + ' B';
            if (n >= 1e9) return (n / 1e9).toFixed(2).replace(/\.?0+$/, '') + ' Mrd';
            if (n >= 1e6) return (n / 1e6).toFixed(2).replace(/\.?0+$/, '') + ' M';
            if (n >= 1e3) return (n / 1e3).toFixed(2).replace(/\.?0+$/, '') + ' K';
            return n.toLocaleString('fr-DZ', {
                maximumFractionDigits: 2
            });
        }

        fetch(API, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get_stats'
                })
            })
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                const d = res.data;
                document.getElementById('stat-total-factures').textContent = d.total_factures.toLocaleString('fr-DZ');
                document.getElementById('stat-ca').textContent = fmt(d.ca_total_dzd) + ' DZD';
                document.getElementById('stat-fournisseurs').textContent = d.fournisseurs_actifs;
                document.getElementById('stat-contrats').textContent = d.contrats_en_cours;
            })
            .catch(() => {
                ['stat-total-factures', 'stat-ca', 'stat-fournisseurs', 'stat-contrats'].forEach(id => {
                    document.getElementById(id).textContent = '—';
                });
            });
    })();
</script>
