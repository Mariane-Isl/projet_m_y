<?php
error_log("nous sommes dans receptionner_bordereau.php");
session_start();

require_once '../../classes/Database.php';
require_once '../../classes/Bordereau.php';
require_once '../../classes/historique_borderau.php';


$bordereau_id = 0;
// ── 1. Récupération ID bordereau ──────────────────────────────────────────
// Méthode POST uniquement — l'ID est stocké en SESSION pour toute la durée de la page
if (isset($_POST['bordereau_id']) && intval($_POST['bordereau_id']) > 0) {

    error_log("ID de bordereau reçu via POST : " . $_POST['bordereau_id']);
    // $_SESSION['receptionner_bordereau_id'] = intval($_POST['bordereau_id']);
    $bordereau_id = intval($_POST['bordereau_id']);
}



if ($bordereau_id <= 0) {
    error_log("ID de bordereau invalide ou manquant : " . ($bordereau_id ?? 'null'));
    header("Location: Reception_bordereaux.php");
    exit();
}

// ── 2. Connexion BDD ──────────────────────────────────────────────────────
$database = new Database();
$db       = $database->getConnection();
$brd      = historique_borderau::getDetailsBordereau($db, $bordereau_id);

error_log("Bordereau récupéré : " . ($brd ? "ID " . $brd['bordereau_id'] : "Aucun bordereau trouvé pour ID " . $bordereau_id));


if (Bordereau::hasStatut($db, $bordereau_id, 'RECEPTION')) {
    $_SESSION['flash_message'] = "Ce bordereau a déjà été réceptionné.";
    $_SESSION['flash_type']    = "warning";
    header("Location: Reception_bordereaux.php");
    exit();
}



// ── 7. Factures du bordereau ──────────────────────────────────────────────
$queryFactures = "
    SELECT
        f.id,
        f.Num_facture,
        f.Date_facture,
        f.Montant,
        m.code      AS monnaie_code,
        m.label     AS monnaie_label,
        sf.code     AS statut_code,
        sf.label    AS statut_label
    FROM Facture f
    LEFT JOIN money m ON m.id = f.money_id
    LEFT JOIN (
        SELECT hx.Factureid, hx.statut_factureid
        FROM historique_facture hx
        INNER JOIN (
            SELECT Factureid, MAX(date_statuts) AS max_date
            FROM historique_facture
            GROUP BY Factureid
        ) latest ON latest.Factureid = hx.Factureid AND latest.max_date = hx.date_statuts
        GROUP BY hx.Factureid
    ) hf ON hf.Factureid = f.id
    LEFT JOIN statut_facture sf ON sf.id = hf.statut_factureid
    WHERE f.Bordereau_id = :bordereau_id
    ORDER BY f.id ASC
";
$stmtFact = $db->prepare($queryFactures);
$stmtFact->bindParam(':bordereau_id', $bordereau_id, PDO::PARAM_INT);
$stmtFact->execute();
$factures = $stmtFact->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Réception — " . htmlspecialchars($brd['num_bordereau']);
?>
<?php include '../Includes/header.php'; ?>
<?php include '../Includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="content-area">

        <!-- ══ BREADCRUMB ══════════════════════════════════════════════════════════ -->
        <div class="mt-3 mb-3" style="font-size:0.81rem;color:var(--text-muted);">
            <a href="../dashboard.php" style="color:var(--text-muted);text-decoration:none;">Accueil</a>
            <span style="margin:0 6px;">/</span>
            <a href="Reception_bordereaux.php" style="color:var(--text-muted);text-decoration:none;">Réception
                Bordereaux</a>
            <span style="margin:0 6px;">/</span>
            <span style="color:var(--accent-blue);"><?= htmlspecialchars($brd['num_bordereau']) ?></span>
        </div>

        <!-- ══ TITRE ══════════════════════════════════════════════════════════════ -->
        <h4
            style="font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1px;margin-bottom:20px;color:var(--text-primary);">
            Réception du Bordereau N°
            <span style="color:var(--accent-blue);"><?= htmlspecialchars($brd['num_bordereau']) ?></span>
        </h4>

        <!-- ══ CARTE DÉTAILS ══════════════════════════════════════════════════════ -->
        <div class="section-card mb-4" style="padding:20px 28px;">

            <div
                style="display:flex;align-items:center;gap:8px;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,0.07);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="var(--accent-blue)">
                    <path
                        d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                </svg>
                <span style="font-weight:700;font-size:0.87rem;color:var(--text-primary);">Détails du Bordereau</span>
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
                            <td style="font-weight:600;">
                                <?= htmlspecialchars(($brd['emetteur_nom'] ?? '') . ' ' . ($brd['emetteur_prenom'] ?? '—')) ?>
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
        </div>

        <!-- ══ TABLEAU FACTURES ════════════════════════════════════════════════════ -->
        <div class="section-card">
            <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;">
                <h3 class="section-title">Liste des Factures</h3>
                <button id="btnReceptionner" onclick="confirmerReception()"
                    style="background:var(--accent-blue);color:#fff;font-weight:700;padding:7px 20px;border-radius:8px;border:none;cursor:pointer;font-size:0.83rem;display:flex;align-items:center;gap:6px;transition:opacity 0.2s;"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z" />
                    </svg>
                    ↓ Réceptionner
                </button>
            </div>

            <!-- Formulaire caché -->
            <form id="formReceptionner" action="../../Controllers/LOCAL_API/Bordereaux/receptioon_final_brd.php"
                method="POST" style="display:none;">

                <input type="hidden" name="bordereau_id" value="<?= htmlspecialchars($bordereau_id) ?>">
            </form>

            <div class="table-responsive">
                <table class="factures-table" id="tableFactures">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Date Facture</th>
                            <th>Montant</th>
                            <th>Monnaie</th>
                            <th>Structure</th>
                            <th style="text-align:center;">Statut (Trouvé/Non trouvé)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($factures)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="rgba(255,255,255,0.15)"
                                        style="display:block;margin:0 auto 8px;">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                                    </svg>
                                    Aucune facture trouvée pour ce bordereau.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($factures as $fact): ?>
                                <?php
                                $sCode     = $fact['statut_code'] ?? '';
                                $estTrouve = ($sCode === 'RECU');
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge-status badge-process" style="font-size:0.78rem;">
                                            <?= htmlspecialchars($fact['Num_facture']) ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--text-muted);font-size:0.82rem;">
                                        <?= !empty($fact['Date_facture']) ? htmlspecialchars(date('d/m/Y', strtotime($fact['Date_facture']))) : '—' ?>
                                    </td>
                                    <td style="font-weight:600;">
                                        <?= number_format(floatval($fact['Montant']), 2, '.', ' ') ?>
                                        <span
                                            style="color:var(--text-muted);font-size:0.78rem;"><?= htmlspecialchars($fact['monnaie_code'] ?? '') ?></span>
                                    </td>
                                    <td style="font-size:0.83rem;">
                                        <?= htmlspecialchars($fact['monnaie_label'] ?? $fact['monnaie_code'] ?? '—') ?></td>
                                    <td style="font-size:0.82rem;color:var(--text-muted);">SIEGE-DP</td>
                                    <td style="text-align:center;">
                                        <div style="display:inline-flex;align-items:center;gap:10px;">
                                            <button class="tog-btn <?= $estTrouve ? 'tog-on' : 'tog-off' ?>"
                                                data-facture-id="<?= $fact['id'] ?>" data-trouve="<?= $estTrouve ? '1' : '0' ?>"
                                                onclick="toggleStatut(this)" title="Cliquer pour basculer"
                                                style="border:none;cursor:pointer;background:none;padding:0;">
                                                <div class="tog-track <?= $estTrouve ? 'tog-on' : 'tog-off' ?>">
                                                    <div class="tog-thumb"></div>
                                                </div>
                                            </button>
                                            <span class="tog-label" style="font-size:0.8rem;min-width:65px;text-align:left;
                                         color:<?= $estTrouve ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                                                <?= $estTrouve ? 'Trouvé' : 'Non trouvé' ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include '../Includes/footer.php'; ?>
    </div><!-- /content-area -->



    <style>
        /* ── Toggle interactif ─────────────────────────────── */
        .tog-btn {
            outline: none;
        }

        .tog-track {
            width: 42px;
            height: 22px;
            border-radius: 22px;
            position: relative;
            transition: background .25s;
            cursor: pointer;
        }

        .tog-off {
            background: #e74c3c;
        }

        .tog-on {
            background: #00c875;
        }

        .tog-thumb {
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            position: absolute;
            top: 3px;
            transition: left .25s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
        }

        .tog-off .tog-thumb {
            left: 3px;
        }

        .tog-on .tog-thumb {
            left: 23px;
        }

        @keyframes tog-pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.08);
            }

            100% {
                transform: scale(1);
            }
        }

        .tog-track.tog-anim {
            animation: tog-pulse .2s ease;
        }

        .tog-loading .tog-thumb {
            background: rgba(255, 255, 255, 0.5);
            cursor: wait;
        }

        /* ── Hover rows ── */
        #tableFactures tbody tr:hover {
            background: rgba(0, 191, 255, 0.04);
        }

        /* ══ DataTable dark theme ══════════════════════════════ */
        #tableFactures_wrapper .dataTables_length label,
        #tableFactures_wrapper .dataTables_filter label,
        #tableFactures_wrapper .dataTables_info {
            color: var(--text-muted) !important;
            font-size: 0.82rem !important;
        }

        #tableFactures_wrapper .dataTables_length select,
        #tableFactures_wrapper .dataTables_filter input {
            background: var(--card-bg, #1a1d26) !important;
            color: var(--text-main, #e8eaf0) !important;
            border: 1px solid var(--card-border, #242733) !important;
            border-radius: 6px !important;
            padding: 4px 8px !important;
            font-size: 0.82rem !important;
            outline: none !important;
        }

        #tableFactures_wrapper .dataTables_filter input:focus {
            border-color: var(--accent-blue, #00bfff) !important;
            box-shadow: 0 0 0 2px rgba(0, 191, 255, 0.15) !important;
        }

        /* Pagination */
        #tableFactures_wrapper .dataTables_paginate {
            margin-top: 12px !important;
        }

        #tableFactures_wrapper .dataTables_paginate .paginate_button {
            background: var(--card-bg, #1a1d26) !important;
            border: 1px solid var(--card-border, #242733) !important;
            color: var(--text-muted, rgba(255, 255, 255, 0.4)) !important;
            border-radius: 6px !important;
            padding: 4px 12px !important;
            margin: 0 2px !important;
            font-size: 0.82rem !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
        }

        #tableFactures_wrapper .dataTables_paginate .paginate_button:hover {
            background: rgba(0, 191, 255, 0.1) !important;
            border-color: var(--accent-blue, #00bfff) !important;
            color: var(--accent-blue, #00bfff) !important;
        }

        #tableFactures_wrapper .dataTables_paginate .paginate_button.current,
        #tableFactures_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--accent-blue, #00bfff) !important;
            border-color: var(--accent-blue, #00bfff) !important;
            color: #fff !important;
            font-weight: 700 !important;
        }

        #tableFactures_wrapper .dataTables_paginate .paginate_button.disabled,
        #tableFactures_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            opacity: 0.35 !important;
            cursor: not-allowed !important;
            background: var(--card-bg, #1a1d26) !important;
            color: var(--text-muted) !important;
        }

        /* Wrapper layout */
        #tableFactures_wrapper .dataTables_length,
        #tableFactures_wrapper .dataTables_filter {
            padding: 0 4px 12px 4px !important;
        }

        #tableFactures_wrapper .dataTables_info,
        #tableFactures_wrapper .dataTables_paginate {
            padding: 12px 4px 0 4px !important;
            display: inline-block !important;
        }

        #tableFactures_wrapper .dataTables_paginate {
            float: right !important;
        }
    </style>

    <script>
        // ════ DataTable ════
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $.fn.DataTable !== 'undefined') {
                $('#tableFactures').DataTable({
                    language: {
                        url: '../../dist/language/datatableplugin.json'
                    },
                    paging: false,
                    lengthChange: false,
                    info: false,
                    order: [
                        [0, 'asc']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: [1, 5]
                    }]
                });
            }
        });

        // ════ Toggle Statut Facture ════
        function toggleStatut(btn) {
            const factureId = btn.dataset.factureId;
            const estTrouve = btn.dataset.trouve === '1';
            const newTrouve = !estTrouve; // on inverse
            const track = btn.querySelector('.tog-track');
            const label = btn.parentElement.querySelector('.tog-label');

            // Désactiver pendant requête
            btn.disabled = true;
            track.classList.add('tog-loading');

            const fd = new FormData();
            fd.append('facture_id', factureId);
            fd.append('trouve', newTrouve ? '1' : '0');

            fetch('../../Controllers/LOCAL_API/Factures/toggle_statut_facture.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    track.classList.remove('tog-loading');

                    if (data.success) {
                        // Mettre à jour data
                        btn.dataset.trouve = newTrouve ? '1' : '0';

                        // Animer + changer couleur track
                        track.classList.remove('tog-on', 'tog-off');
                        track.classList.add(newTrouve ? 'tog-on' : 'tog-off');
                        track.classList.add('tog-anim');
                        setTimeout(() => track.classList.remove('tog-anim'), 220);

                        // Changer couleur du label
                        label.textContent = newTrouve ? 'Trouvé' : 'Non trouvé';
                        label.style.color = newTrouve ? 'var(--accent-green)' : 'var(--accent-red)';

                        // Toast discret
                        Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: newTrouve ? 'success' : 'warning',
                            title: newTrouve ? 'Marqué comme Trouvé' : 'Marqué comme Non trouvé',
                            showConfirmButton: false,
                            timer: 1800,
                            timerProgressBar: true
                        });
                    } else {
                        Swal.fire('Erreur', data.message || 'Impossible de changer le statut.', 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    track.classList.remove('tog-loading');
                    Swal.fire('Erreur', 'Erreur de connexion.', 'error');
                });
        }


        function confirmerReception() {
            Swal.fire({
                title: 'Confirmer la réception ?',
                // Utilisation des ` (backticks) pour écrire du HTML multiligne sans erreur
                html: `
            Le bordereau <strong><?= addslashes(htmlspecialchars($brd['num_bordereau'])) ?></strong> sera marqué comme <strong>Réceptionné</strong>.<br><br>
            <div style="margin-top:10px;">
                <label style="font-size:0.85rem;color:#aaa;display:block;margin-bottom:5px;">Date de réception</label>
                <div style="width:100%;padding:7px 12px;border-radius:6px;border:1px solid #444;background:#2a3145;color:#e0e6f0;font-size:0.88rem;text-align:center;font-weight:600;">
                    <?= date('d/m/Y') ?>
                </div>
            </div>
        `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b9eff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '✅ Confirmer la réception',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {

                    const btn = document.getElementById('btnReceptionner');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" ...></span> Enregistrement...';

                    // ✅ CORRECTION : collecter les factures NON trouvées (toggle en rouge)
                    const form = document.getElementById('formReceptionner');

                    // Nettoyer les anciens champs injectés (au cas où)
                    form.querySelectorAll('input[name="factures_introuvables[]"]').forEach(el => el.remove());

                    // Parcourir tous les toggles : si data-trouve="0" → introuvable
                    document.querySelectorAll('.tog-btn[data-trouve="0"]').forEach(function(toggleBtn) {
                        const factureId = toggleBtn.dataset.factureId;
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'factures_introuvables[]';
                        input.value = factureId;
                        form.appendChild(input);
                    });

                    document.getElementById('formReceptionner').submit();
                }
            });
        }
    </script>

</div><!-- /main-content -->