<?php
/**
 * VUE : Détail d'un Bordereau — Dark Theme
 * Chemin : Pages/Bordereaux/detail_bordereau.php
 */
session_start();

// Lecture initiale via POST si on vient d'un formulaire, sinon session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bordereau_id'])) {
    $bordereau_id = intval($_POST['bordereau_id']);
    $_SESSION['current_bordereau_id'] = $bordereau_id;
} elseif (isset($_SESSION['current_bordereau_id'])) {
    $bordereau_id = $_SESSION['current_bordereau_id'];
} else {
    header("Location: liste_bordereaux_recus.php");
    exit();
}

if ($bordereau_id <= 0) {
    header("Location: liste_bordereaux_recus.php");
    exit();
}

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Bordereau.php';

$database  = new Database();
$db        = $database->getConnection();
$bordereau = Bordereau::getByIdWithDetails($db, $bordereau_id);

if (!$bordereau) {
    $_SESSION['flash_message'] = "Bordereau introuvable.";
    $_SESSION['flash_type']    = "danger";
    header("Location: liste_bordereaux_recus.php");
    exit();
}

$factures       = Bordereau::getFacturesWithStatutByBordereauId($db, $bordereau_id);
$nb_total       = count($factures);
$nb_trouves     = 0;
$nb_non_trouves = 0;
foreach ($factures as $fac) {
    if (!empty($fac['statut_code']) && $fac['statut_code'] !== 'TRANSMIS') {
        $nb_trouves++;
    } else {
        $nb_non_trouves++;
    }
}

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type    = $_SESSION['flash_type']    ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bordereau <?= htmlspecialchars($bordereau['num_bordereau']) ?> — PGSF</title>

  
    
    <style>
        :root {
            --bg:           #0f1117;
            --surface:      #1a1f2e;
            --surface2:     #242a3e;
            --border:       #2d3748;
            --text:         #e2e8f0;
            --text-muted:   #64748b;
            --text-sub:     #94a3b8;
            --blue:         #3b82f6;
            --blue-light:   #60a5fa;
            --green:        #10b981;
            --green-light:  #34d399;
            --red:          #ef4444;
            --red-light:    #f87171;
            --amber:        #fbbf24;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .app-topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: .75rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-bc { font-size: .82rem; color: var(--text-sub); }
        .topbar-bc a { color: var(--blue-light); text-decoration: none; font-weight: 500; }
        .topbar-bc a:hover { text-decoration: underline; }
        .topbar-bc .sep { margin: 0 .4rem; color: #4a5568; }

        /* ── CONTENT ── */
        .app-content { padding: 1.75rem; }

        /* ── STAT CARDS ── */
        .stat-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            border: 1px solid var(--border);
            border-top: 3px solid transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card.blue  { border-top-color: var(--blue); }
        .stat-card.green { border-top-color: var(--green); }
        .stat-card.red   { border-top-color: var(--red); }
        .stat-val { font-size: 2rem; font-weight: 800; line-height: 1; color: #f1f5f9; }
        .stat-lbl { font-size: .7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .07em; margin-top: .3rem; }
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; }
        .si-blue  { background: rgba(59,130,246,.15);  color: var(--blue-light); }
        .si-green { background: rgba(16,185,129,.15);  color: var(--green-light); }
        .si-red   { background: rgba(239,68,68,.15);   color: var(--red-light); }

        /* ── SEC CARDS ── */
        .sec-card { background: var(--surface); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; margin-bottom: 1.5rem; }
        .sec-card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .sc-icon {
            width: 34px; height: 34px; border-radius: 8px;
            background: rgba(59,130,246,.15);
            color: var(--blue-light);
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem;
        }
        .sec-card-header h2 { font-size: .95rem; font-weight: 700; color: #f1f5f9; margin: 0; }
        .sec-card-body { padding: 1.5rem; }

        /* ── INFO GRID ── */
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .info-lbl { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); margin-bottom: .25rem; }
        .info-val { font-size: .92rem; font-weight: 600; color: var(--text); }
        .info-val.mono { font-family: 'Courier New', monospace; color: var(--blue-light); }

        /* ── BADGES STATUT BORDEREAU ── */
        .sb { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .85rem; border-radius: 9999px; font-size: .75rem; font-weight: 700; }
        .sb-arrive  { background: rgba(251,191,36,.15); color: var(--amber); }
        .sb-info    { background: rgba(59,130,246,.15); color: var(--blue-light); }
        .sb-success { background: rgba(16,185,129,.15); color: var(--green-light); }
        .sb-default { background: rgba(148,163,184,.1); color: var(--text-sub); }

        /* ── TABLEAU FACTURES ── */
        .fac-table-wrap { overflow-x: auto; }
        .fac-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .fac-table thead tr { background: var(--surface2); }
        .fac-table th {
            padding: .75rem 1rem;
            font-size: .7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .07em;
            color: var(--text-sub); text-align: left;
            border: none; white-space: nowrap;
        }
        .fac-table td {
            padding: .75rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: #cbd5e1;
        }
        .fac-table tbody tr:last-child td { border-bottom: none; }
        .fac-table tbody tr:hover { background: #1f2740; }
        .fac-table tfoot td {
            background: var(--surface2);
            color: var(--text-sub);
            font-weight: 700;
            border: none;
            padding: .75rem 1rem;
        }
        .row-found td:first-child     { border-left: 3px solid var(--green); }
        .row-not-found td:first-child { border-left: 3px solid var(--red); }

        /* Badges statut facture */
        .badge-found {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .22rem .65rem; border-radius: 9999px;
            background: rgba(16,185,129,.15); color: var(--green-light);
            font-size: .72rem; font-weight: 700;
        }
        .badge-not-found {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .22rem .65rem; border-radius: 9999px;
            background: rgba(239,68,68,.15); color: var(--red-light);
            font-size: .72rem; font-weight: 700;
        }
        .num-fac { font-family: 'Courier New', monospace; font-weight: 700; color: var(--blue-light); }

        /* ── BOUTONS ── */
        .btn-pdf {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .55rem 1.35rem;
            background: var(--blue); color: #fff;
            border: none; border-radius: 8px;
            font-size: .875rem; font-weight: 700;
            cursor: pointer; transition: background .2s, transform .15s;
            text-decoration: none;
        }
        .btn-pdf:hover { background: #2563eb; transform: translateY(-1px); color: #fff; }
        .btn-pdf:active { transform: translateY(0); }

        .btn-back {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .55rem 1.25rem;
            background: transparent; color: var(--text-sub);
            border: 1px solid var(--border); border-radius: 8px;
            font-size: .875rem; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: background .2s, color .2s;
        }
        .btn-back:hover { background: var(--surface2); color: var(--text); }

        /* ── BADGE COUNT ── */
        .badge-count {
            background: var(--surface2); color: var(--text-sub);
            padding: .2rem .7rem; border-radius: 9999px;
            font-size: .75rem; font-weight: 700;
        }

        /* ── FLASH ── */
        .alert { border-radius: 10px; border: none; font-size: .875rem; }
        .alert-danger  { background: rgba(239,68,68,.15);  color: var(--red-light); }
        .alert-success { background: rgba(16,185,129,.15); color: var(--green-light); }
        .alert-info    { background: rgba(59,130,246,.15); color: var(--blue-light); }

        @media (max-width: 576px) {
            .app-content { padding: 1rem; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

<!-- TOPBAR -->
<div class="app-topbar">
    <div class="topbar-bc">
        <a href="#">Accueil</a>
        <span class="sep">/</span>
        <a href="liste_bordereaux_recus.php">Bordereaux</a>
        <span class="sep">/</span>
        <span><?= htmlspecialchars($bordereau['num_bordereau']) ?></span>
    </div>

    <form method="POST"
          action="../../Controllers/Bordereux/PdfBordereauController.php"
          target="_blank">
        <input type="hidden" name="action"       value="generer_pdf_bordereau">
        <input type="hidden" name="bordereau_id" value="<?= (int)$bordereau_id ?>">
        <button type="submit" class="btn-pdf">
            <i class="fa-solid fa-file-pdf"></i>
            Générer PDF
        </button>
    </form>
</div>

<!-- CONTENU -->
<div class="app-content">

    <!-- Flash -->
    <?php if ($flash_message): ?>
        <div class="alert alert-<?= htmlspecialchars($flash_type) ?> alert-dismissible fade show mb-4" role="alert">
            <?= htmlspecialchars($flash_message) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="stat-card blue">
                <div>
                    <div class="stat-val"><?= $nb_total ?></div>
                    <div class="stat-lbl">Factures total</div>
                </div>
                <div class="stat-icon si-blue"><i class="fa-solid fa-file-invoice"></i></div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card green">
                <div>
                    <div class="stat-val"><?= $nb_trouves ?></div>
                    <div class="stat-lbl">Trouvées</div>
                </div>
                <div class="stat-icon si-green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card red">
                <div>
                    <div class="stat-val"><?= $nb_non_trouves ?></div>
                    <div class="stat-lbl">Non trouvées</div>
                </div>
                <div class="stat-icon si-red"><i class="fa-solid fa-circle-xmark"></i></div>
            </div>
        </div>
    </div>

    <!-- CARTE 1 : Infos bordereau -->
    <div class="sec-card">
        <div class="sec-card-header">
            <div class="sc-icon"><i class="fa-solid fa-file-lines"></i></div>
            <h2>Informations du Bordereau</h2>
            <div class="ms-auto">
                <?php
                $sc = $bordereau['statut_code'] ?? '';
                [$sbCls, $sbIcon] = match($sc) {
                    'ARRIVE'       => ['sb-arrive',  'fa-envelope-open'],
                    'NON_CONTROLE' => ['sb-info',    'fa-hourglass-half'],
                    'CONTROLE','RECEPTION' => ['sb-success', 'fa-circle-check'],
                    default        => ['sb-default', 'fa-circle'],
                };
                ?>
                <span class="sb <?= $sbCls ?>">
                    <i class="fa-solid <?= $sbIcon ?>"></i>
                    <?= htmlspecialchars($bordereau['statut_label'] ?? 'Inconnu') ?>
                </span>
            </div>
        </div>
        <div class="sec-card-body">
            <div class="info-grid">
                <div>
                    <div class="info-lbl">N° Bordereau</div>
                    <div class="info-val mono"><?= htmlspecialchars($bordereau['num_bordereau']) ?></div>
                </div>
                <div>
                    <div class="info-lbl">Date</div>
                    <div class="info-val"><?= !empty($bordereau['date_bordereau']) ? date('d/m/Y', strtotime($bordereau['date_bordereau'])) : '—' ?></div>
                </div>
                <div>
                    <div class="info-lbl">Contrat N°</div>
                    <div class="info-val mono"><?= htmlspecialchars($bordereau['num_contrat'] ?? '—') ?></div>
                </div>
                <div>
                    <div class="info-lbl">Fournisseur</div>
                    <div class="info-val"><?= htmlspecialchars($bordereau['nom_Fournisseur'] ?? '—') ?></div>
                </div>
                <div>
                    <div class="info-lbl">Émetteur</div>
                    <div class="info-val"><?= htmlspecialchars(trim(($bordereau['emetteur_nom'] ?? '') . ' ' . ($bordereau['emetteur_prenom'] ?? ''))) ?></div>
                </div>
                <div>
                    <div class="info-lbl">Structure</div>
                    <div class="info-val"><?= htmlspecialchars($bordereau['region_label'] ?? '—') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARTE 2 : Tableau factures -->
    <div class="sec-card">
        <div class="sec-card-header">
            <div class="sc-icon"><i class="fa-solid fa-table-list"></i></div>
            <h2>Liste des Factures</h2>
            <span class="ms-auto badge-count"><?= $nb_total ?> facture(s)</span>
        </div>
        <div class="fac-table-wrap">
            <table class="fac-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>N° Facture</th>
                        <th>Date Facture</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center">Devise</th>
                        <th>Statut</th>
                        <th class="text-center">Date Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($factures)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 fst-italic" style="color:var(--text-muted)">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>
                                Aucune facture attachée à ce bordereau.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($factures as $i => $fac): ?>
                            <?php
                            $estTrouvee = !empty($fac['statut_code']) && $fac['statut_code'] !== 'TRANSMIS';
                            $rowCls     = $estTrouvee ? 'row-found' : 'row-not-found';
                            $badgeCls   = $estTrouvee ? 'badge-found' : 'badge-not-found';
                            $badgeIco   = $estTrouvee ? 'fa-circle-check' : 'fa-circle-xmark';
                            $badgeTxt   = $estTrouvee ? htmlspecialchars($fac['statut_label']) : 'Non trouvée';
                            ?>
                            <tr class="<?= $rowCls ?>">
                                <td style="color:#4a5568;font-size:.78rem"><?= $i + 1 ?></td>
                                <td><span class="num-fac"><?= htmlspecialchars($fac['Num_facture'] ?? '—') ?></span></td>
                                <td><?= !empty($fac['date_facture']) ? date('d/m/Y', strtotime($fac['date_facture'])) : '—' ?></td>
                                <td class="text-end" style="font-family:'Courier New',monospace">
                                    <?= $fac['Montant'] !== null ? number_format((float)$fac['Montant'], 2, ',', ' ') : '—' ?>
                                </td>
                                <td class="text-center fw-bold"><?= htmlspecialchars($fac['devise'] ?? '—') ?></td>
                                <td>
                                    <span class="<?= $badgeCls ?>">
                                        <i class="fa-solid <?= $badgeIco ?>" style="font-size:10px"></i>
                                        <?= $badgeTxt ?>
                                    </span>
                                </td>
                                <td class="text-center" style="color:var(--text-muted);font-size:.82rem">
                                    <?= !empty($fac['date_statut']) ? date('d/m/Y', strtotime($fac['date_statut'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end">Nombre total de factures :</td>
                        <td colspan="2" class="text-center">
                            <?= $nb_total ?> total — <?= $nb_trouves ?> trouvée(s) — <?= $nb_non_trouves ?> non trouvée(s)
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- CARTE 3 : Actions -->
    <div class="sec-card">
        <div class="sec-card-header">
            <div class="sc-icon"><i class="fa-solid fa-bolt"></i></div>
            <h2>Actions</h2>
        </div>
        <div class="sec-card-body">
            <div class="d-flex gap-3 flex-wrap align-items-center">
                <form method="POST"
                        action="../../Controllers/Bordereux/PdfBordereauController.php"
                      target="_blank">
                    <input type="hidden" name="action"       value="generer_pdf_bordereau">
                    <input type="hidden" name="bordereau_id" value="<?= (int)$bordereau_id ?>">
                    <button type="submit" class="btn-pdf">
                        <i class="fa-solid fa-file-pdf"></i>
                        Télécharger le PDF
                    </button>
                </form>

                <a href="liste_bordereaux_recus.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour à la liste
                </a>
            </div>
            <p class="mt-3 mb-0" style="font-size:.8rem;color:var(--text-muted)">
                <i class="fa-solid fa-circle-info me-1"></i>
                Le PDF sera généré au format A4 avec le tableau complet des factures et les zones de signature.
            </p>
        </div>
    </div>

</div><!-- /.app-content -->
</div><!-- /.main-content -->


</body>
</html>