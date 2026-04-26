<?php

/**
 * ════════════════════════════════════════════════════════════════
 * OV_detailles.php — Détails d'un Ordre de Virement 
 * ════════════════════════════════════════════════════════════════
 */
session_start();

// On vérifie si on vient de la page Recap via le POST
$is_readonly = false;
if (isset($_POST['source']) && $_POST['source'] === 'recap') {
    $is_readonly = true;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Récupération de l'ov_id (POST en priorité : id si Recap, ov_id si normal)
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $ov_id = intval($_POST['id']);
    $_SESSION['current_ov_id'] = $ov_id;
} elseif (isset($_POST['ov_id']) && is_numeric($_POST['ov_id'])) {
    $ov_id = intval($_POST['ov_id']);
    $_SESSION['current_ov_id'] = $ov_id;
} elseif (isset($_SESSION['current_ov_id'])) {
    $ov_id = intval($_SESSION['current_ov_id']);
} else {
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Accès invalide</title>
    <style>body{background:#0d0f14;color:#e8ecf5;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:16px;}
    .msg{background:#1a1e2b;border:1px solid #ff4f6b;color:#ff4f6b;padding:20px 32px;border-radius:10px;font-size:15px;}
    a{color:#4a9eff;font-size:13px;}</style></head><body>
    <div class="msg">⚠️ Accès invalide – veuillez passer par la liste des OV.</div>
    <a href="javascript:history.back()">← Retour</a></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails OV </title>
    <script src="...bootstrap..."></script>
    <script src="js/bootstrap.bundle.min.js"></script>

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-base: #0d0f14;
            --bg-card: #141720;
            --bg-card-alt: #1a1e2b;
            --bg-hover: #1f2434;
            --border: #252a3a;
            --border-bright: #2e3550;
            --gold: #f5a623;
            --green: #00d4a0;
            --red: #ff4f6b;
            --blue: #4a9eff;
            --text-primary: #e8ecf5;
            --text-secondary: #8891ab;
            --text-muted: #525d7a;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --transition: 0.2s ease;
            --font-sans: 'IBM Plex Sans', sans-serif;
            --font-mono: 'IBM Plex Mono', monospace;
        }

        html,
        body {
            min-height: 100vh;
            background: var(--bg-base);
            color: var(--text-primary);
            font-family: var(--font-sans);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-size: 15px;
            font-weight: 600;
        }

        .topbar-date {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-muted);
        }

        .btn-back {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--bg-card-alt);
            border: 1px solid var(--border-bright);
            color: var(--text-secondary);
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 13px;
            font-family: var(--font-sans);
            transition: var(--transition);
        }

        .btn-back:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        /* ── STATUS BANNER ── */
        .status-banner {
            margin: 20px 28px;
            padding: 20px 28px;
            border-radius: var(--radius-lg);
            transition: background .35s, border-color .35s;
        }

        .status-banner.trait { background: var(--gold); }
        .status-banner.atf { background: #1a2a3a; border: 1px solid var(--blue); }
        .status-banner.adb_atf { background: #2a1a3a; border: 1px solid #9b7bff; }
        .status-banner.depo { background: #0d2b1f; border: 1px solid var(--green); }
        .status-banner.paye { background: var(--green); }
        .status-banner.annul { background: #3a1a20; border: 1px solid var(--red); }
        .status-banner.default { background: #1a1e2b; border: 1px solid var(--border-bright); }

        .banner-label {
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #0d0f14;
        }

        .status-banner.atf .banner-label,
        .status-banner.adb_atf .banner-label,
        .status-banner.depo .banner-label,
        .status-banner.annul .banner-label,
        .status-banner.default .banner-label {
            color: var(--text-primary);
        }

        .banner-meta {
            font-size: 12px;
            color: rgba(13, 15, 20, .65);
            margin-top: 4px;
        }

        .status-banner.atf .banner-meta,
        .status-banner.adb_atf .banner-meta,
        .status-banner.depo .banner-meta,
        .status-banner.annul .banner-meta,
        .status-banner.default .banner-meta {
            color: var(--text-secondary);
        }

        /* ── GRID ── */
        .page-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            padding: 0 28px 40px;
        }

        @media (max-width:1100px) {
            .page-grid { grid-template-columns: 1fr; }
        }

        /* ── CARD ── */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .card+.card { margin-top: 16px; }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
        }

        .card-header-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .card-body { padding: 20px; }

        /* ── IDENTITY GRID ── */
        .identity-grid { display: grid; grid-template-columns: repeat(3, 1fr); }

        .id-field {
            padding: 16px 20px;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .id-field:nth-child(3n) { border-right: none; }
        .id-field:nth-last-child(-n+3) { border-bottom: none; }

        .id-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .id-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .id-value.blue { color: var(--blue); }

        /* ── KTP ── */
        .ktp-block {
            display: flex;
            align-items: center;
            background: var(--bg-card-alt);
            border-top: 1px solid var(--border);
        }

        .ktp-left { flex: 1; text-align: center; padding: 18px 20px; }
        .ktp-right { flex: 1; text-align: center; padding: 18px 20px; border-left: 1px solid var(--border); }

        .ktp-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .ktp-value {
            font-family: var(--font-mono);
            font-size: 22px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: .04em;
        }

        .ktp-amount {
            font-family: var(--font-mono);
            font-size: 22px;
            font-weight: 700;
            color: var(--green);
        }

        .btn-ktp-edit {
            font-size: 12px;
            color: var(--blue);
            cursor: pointer;
            background: none;
            border: none;
            margin-top: 6px;
            display: block;
            width: 100%;
            text-align: center;
        }

        .btn-ktp-edit:hover { text-decoration: underline; }

        /* ── TABLE ── */
        .ft { width: 100%; border-collapse: collapse; }
        .ft thead tr { background: var(--bg-card-alt); }

        .ft th {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .ft td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .ft tbody tr:last-child td { border-bottom: none; }
        .ft tbody tr:hover { background: var(--bg-hover); }

        .ft-num { font-family: var(--font-mono); font-size: 13px; }
        .ft-amt { font-family: var(--font-mono); font-weight: 600; color: var(--green); }

        .empty-td {
            text-align: center;
            color: var(--text-muted);
            padding: 28px 16px !important;
            font-style: italic;
        }

        /* ── BADGES ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-red { background: rgba(255, 79, 107, .12); color: var(--red); border: 1px solid rgba(255, 79, 107, .3); }
        .badge-green { background: rgba(0, 212, 160, .1); color: var(--green); border: 1px solid rgba(0, 212, 160, .2); }
        .badge-gold { background: rgba(245, 166, 35, .1); color: var(--gold); border: 1px solid rgba(245, 166, 35, .3); }
        .badge-blue { background: rgba(74, 158, 255, .1); color: var(--blue); border: 1px solid rgba(74, 158, 255, .25); }
        .badge-muted { background: rgba(82, 93, 122, .15); color: var(--text-secondary); border: 1px solid rgba(82, 93, 122, .3); }
        .badge-purple { background: rgba(155, 123, 255, .12); color: #9b7bff; border: 1px solid rgba(155, 123, 255, .3); }

        /* ── BUTTONS ── */
        .btn-sm {
            padding: 4px 12px;
            border-radius: var(--radius-sm);
            font-family: var(--font-sans);
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-danger { background: none; border: 1px solid rgba(255, 79, 107, .35); color: var(--red); }
        .btn-danger:hover { background: rgba(255, 79, 107, .1); }

        .btn-success { background: none; border: 1px solid rgba(0, 212, 160, .35); color: var(--green); }
        .btn-success:hover { background: rgba(0, 212, 160, .1); }

        .btn-block {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: var(--radius-md);
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-green-outline { background: rgba(0, 212, 160, .08); border: 1px solid var(--green); color: var(--green); }
        .btn-green-outline:hover { background: rgba(0, 212, 160, .16); }

        .btn-red-outline { background: transparent; border: 1px solid rgba(255, 79, 107, .4); color: var(--red); }
        .btn-red-outline:hover { background: rgba(255, 79, 107, .08); }

        /* ── DISABLED BUTTONS (Mode Lecture Seule) ── */
        button:disabled, select:disabled {
            opacity: 0.35 !important;
            cursor: not-allowed !important;
            pointer-events: none !important; /* Empêche le hover et le clic */
        }

        /* ── RIGHT PANEL ── */
        .right-panel { display: flex; flex-direction: column; gap: 16px; }

        .retenue-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            color: var(--text-secondary);
        }

        .retenue-row:last-child { border-bottom: none; }
        .retenue-val { font-family: var(--font-mono); color: var(--text-primary); }

        .statut-select {
            width: 100%;
            background: var(--bg-card-alt);
            border: 1px solid var(--border-bright);
            color: var(--text-primary);
            padding: 10px 14px;
            border-radius: var(--radius-md);
            font-family: var(--font-sans);
            font-size: 13px;
            outline: none;
            cursor: pointer;
            appearance: none;
            margin-bottom: 10px;
        }
        .statut-select:focus { border-color: var(--blue); }

        .btn-ok {
            background: var(--blue);
            border: none;
            color: #fff;
            padding: 9px 22px;
            border-radius: var(--radius-md);
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            float: right;
            transition: var(--transition);
        }
        .btn-ok:hover { background: #3a8dee; }

        /* ── HISTORIQUE ── */
        .histo-item { padding: 11px 0; border-bottom: 1px solid var(--border); }
        .histo-item:last-child { border-bottom: none; }
        .histo-date { font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); }
        .histo-label { font-size: 13px; font-weight: 600; margin: 3px 0 1px; }

        /* ── MODALS & TOASTS ... ── */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, .72); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--bg-card); border: 1px solid var(--border-bright); border-radius: var(--radius-lg); padding: 28px; min-width: 340px; }
        .modal-wide { min-width: min(720px, 92vw); max-height: 85vh; overflow-y: auto; }
        .modal-title { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
        .modal-note { font-size: 12px; color: var(--text-muted); margin-bottom: 10px; line-height: 1.5; }
        .modal-foot { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
        .modal-input { width: 100%; background: var(--bg-card-alt); border: 1px solid var(--border-bright); color: var(--text-primary); padding: 10px 14px; border-radius: var(--radius-md); font-family: var(--font-mono); font-size: 15px; outline: none; margin-bottom: 16px; }
        .modal-input:focus { border-color: var(--blue); }
        .btn-cancel { background: var(--bg-card-alt); border: 1px solid var(--border-bright); color: var(--text-secondary); padding: 8px 18px; border-radius: var(--radius-sm); cursor: pointer; }
        .btn-save { background: var(--blue); border: none; color: #fff; padding: 8px 18px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; }
        .btn-save-red { background: var(--red); }
        .confirm-icon { text-align: center; font-size: 34px; color: var(--red); margin-bottom: 10px; }
        .confirm-text { text-align: center; color: var(--text-secondary); font-size: 14px; margin-bottom: 18px; }
        .confirm-text strong { color: var(--text-primary); }

        #toast { position: fixed; bottom: 26px; right: 26px; background: var(--bg-card); border: 1px solid var(--border-bright); color: var(--text-primary); padding: 11px 18px; border-radius: var(--radius-md); font-size: 13px; z-index: 400; display: none; align-items: center; gap: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, .4); }
        #toast.show { display: flex; }
        #toast.ok { border-left: 3px solid var(--green); }
        #toast.err { border-left: 3px solid var(--red); }

        .skeleton { background: linear-gradient(90deg, var(--bg-card-alt) 25%, var(--bg-hover) 50%, var(--bg-card-alt) 75%); background-size: 400%; animation: shimmer 1.4s infinite; border-radius: 4px; height: 14px; }
        @keyframes shimmer { 0% { background-position: 100% } 100% { background-position: -100% } }
    </style>
</head>

<body>

    <!-- TOPBAR -->
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:14px;">
            <!-- Si on vient du recap, on retourne vers recap. Sinon on retourne vers la liste standard -->
            <form method="<?= $is_readonly ? 'GET' : 'POST' ?>" action="<?= $is_readonly ? '../Dashboard/recap_ordres_virement.php' : 'Liste_Ordre_Virement.php' ?>">
                <button type="submit" class="btn-back">
                    <i class="fa fa-arrow-left"></i> Retour
                </button>
            </form>
            <span class="topbar-title">Traitement Ordre de Virement <?= $is_readonly ? ' (Lecture Seule)' : '' ?></span>
        </div>
        <span class="topbar-date" id="topbar-date"></span>
    </header>

    <script>
        // OV_ID injecté par PHP — jamais depuis l'URL
        const OV_ID = <?= (int)$ov_id ?>;
        // Injection de la variable Lecture Seule pour JS
        const IS_READONLY = <?= $is_readonly ? 'true' : 'false' ?>; 
        const API = '../../Controllers/LOCAL_API/OV/';
    </script>

    <!-- BANNER -->
    <div id="status-banner" class="status-banner default">
        <div class="banner-label" id="banner-label">Chargement…</div>
        <div class="banner-meta">Statut Actuel &nbsp;|&nbsp; Dernière mise à jour : <span id="banner-date">–</span></div>
    </div>

    <!-- GRID -->
    <div class="page-grid">

        <div>
            <!-- Fiche identité -->
            <div class="card">
                <div class="card-header">
                    <span class="card-header-title">Fiche d'identité OV</span>
                    <span class="badge badge-muted" id="badge-statut">–</span>
                </div>
                <div class="identity-grid">
                    <div class="id-field">
                        <div class="id-label">Fournisseur</div>
                        <div class="id-value" id="id-fourn">–</div>
                    </div>
                    <div class="id-field">
                        <div class="id-label">Structure</div>
                        <div class="id-value" id="id-struct">–</div>
                    </div>
                    <div class="id-field">
                        <div class="id-label">Nature</div>
                        <div class="id-value" id="id-nature">–</div>
                    </div>
                    <div class="id-field">
                        <div class="id-label">Contrat</div>
                        <div class="id-value blue" id="id-contrat">–</div>
                    </div>
                    <div class="id-field">
                        <div class="id-label">Type Virement</div>
                        <div class="id-value" id="id-type">–</div>
                    </div>
                    <div class="id-field">
                        <div class="id-label">Monnaie</div>
                        <div class="id-value" id="id-monnaie">–</div>
                    </div>
                </div>
                <div class="ktp-block">
                    <div class="ktp-left">
                        <div class="ktp-label">Référence KTP</div>
                        <div class="ktp-value" id="ktp-value">–</div>
                        <button class="btn-ktp-edit" onclick="openKtpModal()" <?= $is_readonly ? 'disabled' : '' ?>>✏️ Modifier</button>
                    </div>
                    <div class="ktp-right">
                        <div class="ktp-label">Montant Total Validé</div>
                        <div class="ktp-amount" id="montant-total">–</div>
                    </div>
                </div>
            </div>

            <!-- Factures liées -->
            <div class="card">
                <div class="card-header">
                    <span class="card-header-title">Factures associées</span>
                    <button class="btn-sm btn-success" onclick="openModalAjouter()" <?= $is_readonly ? 'disabled' : '' ?>>
                        <i class="fa fa-plus"></i> Ajouter Facture
                    </button>
                </div>
                <table class="ft">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-liees">
                        <tr>
                            <td colspan="4" class="empty-td">Chargement…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Colonne droite -->
        <div class="right-panel">

            <div class="card">
                <div class="card-header"><span class="card-header-title">Actions rapides</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
                    <button class="btn-block btn-green-outline" onclick="openModalAjouter()" <?= $is_readonly ? 'disabled' : '' ?>>
                        <i class="fa fa-file-invoice"></i> Ajouter Facture
                    </button>
                    
                    <div class="retenue-row">
                        <span>Retenue (1.5%)</span>
                        <span class="retenue-val" id="retenue-val">–</span>
                    </div>
                    
                    <button class="btn-block btn-red-outline" onclick="openModalSupprimerOV()" <?= $is_readonly ? 'disabled' : '' ?>>
                        <i class="fa fa-trash"></i> Supprimer l'OV
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-header-title">Changer Statut</span></div>
                <div class="card-body">
                    <select class="statut-select" id="sel-statut" <?= $is_readonly ? 'disabled' : '' ?>>
                        <option value="">Sélectionnez…</option>
                    </select>
                    <button class="btn-ok" onclick="changerStatut()" <?= $is_readonly ? 'disabled' : '' ?>>OK</button>
                    <div style="clear:both;"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-header-title">Historique</span></div>
                <div class="card-body" id="histo-body">
                    <div class="skeleton" style="width:60%;margin-bottom:8px;"></div>
                    <div class="skeleton" style="width:80%;margin-bottom:8px;"></div>
                    <div class="skeleton" style="width:50%;"></div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL KTP -->
    <div class="modal-overlay" id="modal-ktp">
        <div class="modal-box">
            <div class="modal-title"><i class="fa fa-pen" style="color:var(--blue);margin-right:8px;"></i>Modifier la Référence KTP</div>
            <input type="text" class="modal-input" id="inp-ktp" placeholder="Référence KTP">
            <div class="modal-foot">
                <button class="btn-cancel" onclick="closeModal('modal-ktp')">Annuler</button>
                <button class="btn-save" onclick="saveKtp()">Enregistrer</button>
            </div>
        </div>
    </div>

    <!-- MODAL AJOUTER FACTURE -->
    <div class="modal-overlay" id="modal-ajouter">
        <div class="modal-box modal-wide">
            <div class="modal-title"><i class="fa fa-file-invoice" style="color:var(--green);margin-right:8px;"></i>Ajouter une Facture à cet OV</div>
            <p class="modal-note">Factures éligibles : même fournisseur · même contrat · même structure · même devise · statut <strong>Réceptionné</strong> · non affectée à un OV.</p>
            <table class="ft">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tbody-eligible">
                    <tr>
                        <td colspan="4" class="empty-td">Chargement…</td>
                    </tr>
                </tbody>
            </table>
            <div class="modal-foot">
                <button class="btn-cancel" onclick="closeModal('modal-ajouter')">Fermer</button>
            </div>
        </div>
    </div>

    <!-- MODAL SUPPRIMER OV -->
    <div class="modal-overlay" id="modal-suppr">
        <div class="modal-box" style="max-width:400px;">
            <div class="confirm-icon"><i class="fa fa-triangle-exclamation"></i></div>
            <div class="modal-title" style="text-align:center;">Supprimer cet OV ?</div>
            <div class="confirm-text">Cette action est <strong>irréversible</strong>. L'OV, ses factures liées et tout son historique seront supprimés.</div>
            <div class="modal-foot">
                <button class="btn-cancel" onclick="closeModal('modal-suppr')">Annuler</button>
                <button class="btn-save btn-save-red" onclick="confirmerSupprimer()"><i class="fa fa-trash"></i> Supprimer</button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="toast"><i id="toast-ico" class="fa fa-check-circle"></i><span id="toast-msg"></span></div>

</body>

</html>
    <script>
        'use strict';

        // ── Date topbar ──────────────────────────────────────────────────
        (function() {
            const d = new Date(),
                J = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
                M = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
            document.getElementById('topbar-date').textContent =
                `${J[d.getDay()]} ${d.getDate()} ${M[d.getMonth()]} ${d.getFullYear()}`;
        })();

        let OV = null; // Stocke la dernière réponse complète de l'API

        document.addEventListener('DOMContentLoaded', chargerOV);

        // ════════════════════════════════════════════════════════════════
        // CHARGEMENT PRINCIPAL — appel POST vers get_ov_details.php
        // ════════════════════════════════════════════════════════════════
        function chargerOV() {
            post(API + 'get_ov_details.php', {
                    ov_id: OV_ID
                })
                .then(res => {
                    if (!res.success) {
                        toast(res.message || 'Erreur de chargement.', 'err');
                        return;
                    }
                    OV = res;
                    renderIdentite(res.ov);
                    renderFacturesLiees(res.factures_liees); 
                    renderHistorique(res.historique);
                    remplirSelectStatuts(res.statuts_disponibles);
                })
                .catch(() => toast('Erreur réseau lors du chargement.', 'err'));
        }

        // ════════════════════════════════════════════════════════════════
        // RENDER — Fiche identité OV
        // ════════════════════════════════════════════════════════════════
        function renderIdentite(ov) {
            set('id-fourn', ov.nom_fournisseur || '–');
            set('id-struct', ov.structure_label || '–');
            set('id-nature', ov.nature_label || '–');
            set('id-contrat', ov.num_Contrat || '–');
            set('id-type', 'Ordre de virement Fournisseurs Étrangers');
            set('id-monnaie', ov.money_code || '–');
            set('ktp-value', ov.Num_KTP || '–');

            const cur = ov.money_code || '';
            const tot = fmtMontant(ov.montant_total);
            set('montant-total', `${tot} ${cur}`);
            set('retenue-val', `${fmtMontant(parseFloat(ov.montant_total || 0) * 0.015)} ${cur}`);

            const statut = ov.dernier_statut || '–';
            set('banner-label', statut.toUpperCase());
            set('banner-date', fmtDate(ov.date_dernier_statut));
            document.getElementById('status-banner').className = 'status-banner ' + bannerClass(statut);
            const badge = document.getElementById('badge-statut');
            badge.textContent = statut;
            badge.className = 'badge ' + badgeClass(statut);
        }

        // ════════════════════════════════════════════════════════════════
        // RENDER — Tableau des factures LIÉES à l'OV
        // ════════════════════════════════════════════════════════════════
        function renderFacturesLiees(factures) {
            const tbody = document.getElementById('tbody-liees');

            if (!factures || factures.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="empty-td">Aucune facture associée à cet OV.</td></tr>`;
                return;
            }

            tbody.innerHTML = factures.map(f => `
        <tr>
            <td><span class="ft-num">${esc(f.Num_facture)}</span></td>
            <td>${fmtDate(f.date_facture)}</td>
            <td>
                <span class="ft-amt">
                    ${fmtMontant(f.Montant)} ${esc(OV?.ov?.money_code || '')}
                </span>
            </td>
            <td>
                <button class="btn-sm btn-danger"
                        onclick="retirerFacture(${f.id}, '${esc(f.Num_facture)}')"
                        ${IS_READONLY ? 'disabled' : ''}>
                    <i class="fa fa-xmark"></i> Retirer
                </button>
            </td>
        </tr>
    `).join('');
        }

        // ════════════════════════════════════════════════════════════════
        // RENDER — Historique des statuts
        // ════════════════════════════════════════════════════════════════
        function renderHistorique(historique) {
            const body = document.getElementById('histo-body');
            if (!historique || !historique.length) {
                body.innerHTML = '<p style="color:var(--text-muted);font-size:13px;text-align:center;">Aucun historique.</p>';
                return;
            }
            body.innerHTML = historique.map(h => `
        <div class="histo-item">
            <div class="histo-date">${fmtDate(h.date_status_OV)}</div>
            <div class="histo-label" style="color:${statutColor(h.statut_label)}">
                ${esc(h.statut_label)}
            </div>
        </div>
    `).join('');
        }

        // ════════════════════════════════════════════════════════════════
        // RENDER — Select des statuts disponibles
        // ════════════════════════════════════════════════════════════════
        function remplirSelectStatuts(statuts) {
            const sel = document.getElementById('sel-statut');
            if (!sel) return; 
            const devise = OV?.ov?.money_code || '';
            const estDZD = (devise === 'DZD');

            const exclus = estDZD ? ['ATF', 'ADB_ATF'] : [];

            sel.innerHTML = '<option value="">Sélectionnez…</option>';
            (statuts || []).forEach(s => {
                if (exclus.includes(s.code)) return;
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = s.label;
                sel.appendChild(o);
            });
        }

        // ════════════════════════════════════════════════════════════════
        // MODAL — Ajouter une facture éligible
        // ════════════════════════════════════════════════════════════════
        function openModalAjouter() {
            if (IS_READONLY) return;
            const tbody = document.getElementById('tbody-eligible');
            tbody.innerHTML = '<tr><td colspan="4" class="empty-td">Chargement…</td></tr>';
            openModal('modal-ajouter');

            post(API + 'get_ov_details.php', {
                ov_id: OV_ID
            }).then(res => {
                const list = res.factures_eligibles || [];
                if (!list.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="empty-td">Aucune facture éligible disponible.</td></tr>';
                    return;
                }
                tbody.innerHTML = list.map(f => `
            <tr>
                <td><span class="ft-num">${esc(f.Num_facture)}</span></td>
                <td>${fmtDate(f.date_facture)}</td>
                <td>
                    <span class="ft-amt">
                        ${fmtMontant(f.Montant)} ${esc(OV?.ov?.money_code || '')}
                    </span>
                </td>
                <td>
                    <button class="btn-sm btn-success"
                            onclick="ajouterFacture(${f.id}, '${esc(f.Num_facture)}')">
                        <i class="fa fa-plus"></i> Ajouter
                    </button>
                </td>
            </tr>
        `).join('');
            });
        }

        // ── Ajouter une facture à l'OV ──────────────────────────────────
        function ajouterFacture(id, num) {
            if (IS_READONLY) return;
            post(API + 'ajouter_factures_ov.php', {
                    ov_id: OV_ID,
                    facture_id: id
                })
                .then(res => {
                    if (!res.success) {
                        toast(res.message, 'err');
                        return;
                    }
                    toast(`Facture ${num} ajoutée.`, 'ok');
                    closeModal('modal-ajouter');
                    updateMontant(res.montant_total);
                    chargerOV();
                });
        }

        // ── Retirer une facture de l'OV ─────────────────────────────────
        function retirerFacture(id, num) {
            if (IS_READONLY) return;
            if (!confirm(`Retirer la facture ${num} de cet OV ?`)) return;
            post(API + 'retirer_facture_ov.php', {
                    ov_id: OV_ID,
                    facture_id: id
                })
                .then(res => {
                    if (!res.success) {
                        toast(res.message, 'err');
                        return;
                    }
                    toast(`Facture ${num} retirée.`, 'ok');
                    updateMontant(res.montant_total);
                    chargerOV();
                });
        }

        // ── Modifier le KTP ─────────────────────────────────────────────
        function openKtpModal() {
            if (IS_READONLY) return;
            document.getElementById('inp-ktp').value =
                document.getElementById('ktp-value').textContent.trim();
            openModal('modal-ktp');
        }

        function saveKtp() {
            if (IS_READONLY) return;
            const val = document.getElementById('inp-ktp').value.trim();
            if (!val) {
                toast('La référence KTP ne peut pas être vide.', 'err');
                return;
            }
            post(API + 'modifier_ktp_ov.php', {
                    ov_id: OV_ID,
                    num_ktp: val
                })
                .then(res => {
                    if (!res.success) {
                        toast(res.message, 'err');
                        return;
                    }
                    set('ktp-value', res.num_ktp);
                    toast('Référence KTP mise à jour.', 'ok');
                    closeModal('modal-ktp');
                });
        }

        // ── Changer le statut ───────────────────────────────────────────
        function changerStatut() {
            if (IS_READONLY) return;
            const val = document.getElementById('sel-statut').value;
            if (!val) {
                toast('Veuillez sélectionner un statut.', 'err');
                return;
            }
            post(API + 'changer_statut_ov.php', {
                    ov_id: OV_ID,
                    statut_id: val
                })
                .then(res => {
                    if (!res.success) {
                        toast(res.message, 'err');
                        return;
                    }
                    toast(`Statut mis à jour : ${res.label}`, 'ok');
                    set('banner-label', (res.label || '').toUpperCase());
                    set('banner-date', res.date || '');
                    document.getElementById('status-banner').className =
                        'status-banner ' + bannerClass(res.label);
                    const badge = document.getElementById('badge-statut');
                    badge.textContent = res.label;
                    badge.className = 'badge ' + badgeClass(res.label);
                    chargerOV();
                });
        }

        // ── Supprimer l'OV ──────────────────────────────────────────────
        function openModalSupprimerOV() {
            if (IS_READONLY) return;
            openModal('modal-suppr');
        }

        function confirmerSupprimer() {
            if (IS_READONLY) return;
            post(API + 'supprimer_ov.php', {
                    ov_id: OV_ID
                })
                .then(res => {
                    if (!res.success) {
                        toast(res.message, 'err');
                        return;
                    }
                    toast('OV supprimé avec succès.', 'ok');
                    setTimeout(() => {
                        const f = document.createElement('form');
                        f.method = 'POST';
                        f.action = 'Liste_Ordre_Virement.php';
                        document.body.appendChild(f);
                        f.submit();
                    }, 1200);
                });
        }

        // ════════════════════════════════════════════════════════════════
        // UTILITAIRES
        // ════════════════════════════════════════════════════════════════
        function updateMontant(val) {
            const cur = OV?.ov?.money_code || '';
            set('montant-total', `${fmtMontant(val)} ${cur}`);
            set('retenue-val', `${fmtMontant(parseFloat(val || 0) * 0.015)} ${cur}`);
        }


        function _code(s) {
            if (!s) return '';
            const upper = s.trim().toUpperCase();
            if (['TRAIT', 'ATF', 'ADB_ATF', 'DEPO', 'PAYE', 'ANNUL'].includes(upper)) return upper;
            const l = s.toLowerCase();
            if (l.includes('cours') || l.includes('trait')) return 'TRAIT';
            if (l.includes('adb')) return 'ADB_ATF';
            if (l.includes('atf')) return 'ATF';
            if (l.includes('dépos') || l.includes('depo')) return 'DEPO';
            if (l.includes('pay')) return 'PAYE';
            if (l.includes('annul')) return 'ANNUL';
            return '';
        }

        function post(url, data) {
            const fd = new FormData();
            Object.keys(data).forEach(k => fd.append(k, data[k]));
            return fetch(url, {
                method: 'POST',
                body: fd
            }).then(r => r.json());
        }

        function set(id, txt) {
            const el = document.getElementById(id);
            if (el) el.textContent = txt;
        }

        function fmtDate(d) {
            if (!d) return '–';
            try {
                const x = new Date(d);
                return `${String(x.getDate()).padStart(2,'0')}/${String(x.getMonth()+1).padStart(2,'0')}/${x.getFullYear()}`;
            } catch {
                return d;
            }
        }

        function fmtMontant(v) {
            return parseFloat(v || 0).toLocaleString('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function esc(s) {
            return String(s || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function bannerClass(s) {
            const map = {
                TRAIT: 'trait', ATF: 'atf', ADB_ATF: 'adb_atf',
                DEPO: 'depo', PAYE: 'paye', ANNUL: 'annul',
            };
            return map[_code(s)] || 'default';
        }


        function badgeClass(s) {
            const map = {
                TRAIT: 'badge-gold', ATF: 'badge-blue', ADB_ATF: 'badge-purple',
                DEPO: 'badge-green', PAYE: 'badge-green', ANNUL: 'badge-red',
            };
            return map[_code(s)] || 'badge-muted';
        }

        function statutColor(s) {
            const map = {
                TRAIT: 'var(--gold)', ATF: 'var(--blue)', ADB_ATF: '#9b7bff',
                DEPO: 'var(--green)', PAYE: 'var(--green)', ANNUL: 'var(--red)',
            };
            return map[_code(s)] || 'var(--text-secondary)';
        }

        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.addEventListener('click', e => {
                if (e.target === m) m.classList.remove('open');
            });
        });

        function toast(msg, type = 'ok') {
            const t = document.getElementById('toast');
            const ico = document.getElementById('toast-ico');
            document.getElementById('toast-msg').textContent = msg;
            t.className = `show ${type}`;
            ico.className = type === 'ok' ? 'fa fa-check-circle' : 'fa fa-circle-xmark';
            clearTimeout(t._t);
            t._t = setTimeout(() => {
                t.className = '';
            }, 3500);
        }
    </script>