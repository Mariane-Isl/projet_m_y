<?php
require_once '../../Controllers/Facture/FactureController.php';
$page_title = "Recherche Factures";
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
        margin-bottom: 24px;
    }

    /* ── Form controls ── */
    .form-label-dark {
        color: #5d666d;
        font-size: 0.70rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.4px;
        margin-bottom: 6px;
        display: block;
    }

    .ctrl-dark {
        background: #0f1215 !important;
        border: 1px solid #24292d !important;
        color: #e8eaf0 !important;
        border-radius: 6px !important;
        font-size: 0.83rem !important;
        padding: 7px 11px !important;
        width: 100%;
        outline: none;
        transition: border-color .2s;
        -webkit-appearance: none;
        appearance: none;
    }

    .ctrl-dark:focus {
        border-color: #00c3ff !important;
        box-shadow: 0 0 0 2px rgba(0, 195, 255, .12) !important;
    }

    .ctrl-dark option {
        background: #15191d;
        color: #e8eaf0;
    }

    .btn-search {
        background: #00c3ff;
        color: #000;
        font-weight: 700;
        border: none;
        border-radius: 6px;
        padding: 8px 22px;
        font-size: 0.83rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: opacity .2s;
        white-space: nowrap;
    }

    .btn-search:hover {
        opacity: .85;
    }

    /* ── Table ── */
    #tableRechercheFacture td,
    #tableRechercheFacture th {
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid #1f2327 !important;
        padding: 13px 14px !important;
        color: #e8eaf0 !important;
        font-size: 0.83rem;
        vertical-align: middle;
    }

    #tableRechercheFacture thead th {
        color: #5d666d !important;
        font-size: 0.70rem !important;
        text-transform: uppercase;
        font-weight: 600;
        border-bottom: 2px solid #24292d !important;
    }

    #tableRechercheFacture tbody tr:hover {
        background: rgba(0, 195, 255, .03) !important;
    }

    .dataTables_empty {
        color: #5d666d !important;
        padding: 40px !important;
        text-align: center;
    }

    .badge-num {
        background: rgba(0, 195, 255, .1);
        color: #00c3ff;
        padding: 3px 11px;
        border-radius: 10px;
        font-size: 0.77rem;
        font-weight: 700;
        border: 1px solid rgba(0, 195, 255, .2);
    }

    /* Statut badges */
    .badge-recu {
        background: rgba(230, 126, 34, .15);
        color: #e67e22;
        border: 1px solid rgba(230, 126, 34, .25);
        padding: 2px 9px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-paye {
        background: rgba(0, 200, 117, .15);
        color: #00c875;
        border: 1px solid rgba(0, 200, 117, .25);
        padding: 2px 9px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-rejeter {
        background: rgba(231, 76, 60, .15);
        color: #e74c3c;
        border: 1px solid rgba(231, 76, 60, .25);
        padding: 2px 9px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-affected {
        background: rgba(59, 158, 255, .15);
        color: #3b9eff;
        border: 1px solid rgba(59, 158, 255, .25);
        padding: 2px 9px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .badge-default-s {
        background: rgba(93, 102, 109, .15);
        color: #8b949e;
        border: 1px solid rgba(93, 102, 109, .25);
        padding: 2px 9px;
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .btn-view {
        background: transparent;
        border: 1px solid #00c3ff; /* Bordure bleue */
        color: #00c3ff;            /* Texte bleu */
        cursor: pointer;
        padding: 6px 12px;         /* Espace à l'intérieur du bouton */
        border-radius: 6px;        /* Coins arrondis */
        transition: all .2s ease;
        line-height: 1.3;
        font-size: 0.75rem;
        text-align: center;
        font-weight: 500;
    }

    .btn-view:hover {
        background: rgba(0, 195, 255, 0.1); /* Léger fond bleu au survol */
        color: #fff;                        /* Texte devient blanc au survol */
        border-color: #00c3ff;
    }

    /* ── DataTable dark wrapper ── */
    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_info {
        color: #5d666d !important;
        font-size: .80rem !important;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background: #0f1215 !important;
        color: #e8eaf0 !important;
        border: 1px solid #24292d !important;
        border-radius: 5px !important;
        padding: 3px 8px !important;
        font-size: .80rem !important;
        outline: none !important;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #00c3ff !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        background: #0f1215 !important;
        color: #5d666d !important;
        border: 1px solid #24292d !important;
        border-radius: 5px !important;
        font-size: .78rem !important;
        padding: 3px 10px !important;
        margin: 0 2px !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #00c3ff !important;
        color: #000 !important;
        border-color: #00c3ff !important;
        font-weight: 700 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: rgba(0, 195, 255, .1) !important;
        color: #00c3ff !important;
        border-color: #00c3ff !important;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        padding: 0 4px 14px !important;
    }

    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        padding: 14px 4px 0 !important;
        display: inline-block !important;
    }

    .dataTables_wrapper .dataTables_paginate {
        float: right !important;
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

    .filter-toggle {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #8b949e;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
</style>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="container-fluid p-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 style="font-weight:700;margin:0;font-size:1.15rem;">
                    Recherche Factures :
                    <span style="color:#00c3ff;">Division Production</span>
                </h4>
            </div>
            <div class="bread">
                <a href="../dashboard.php">Accueil</a>
                <span style="margin:0 5px;">/</span>
                <span style="color:#fff;">Recherche Factures</span>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card-dark">
            <div style="padding:13px 20px;border-bottom:1px solid #1f2327;">
                <div class="filter-toggle">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z" />
                    </svg>
                    Filtres de recherche
                </div>
            </div>
            <div style="padding:16px 20px 20px;">
                <form method="POST" action="../../Controllers/Facture/FactureController.php" id="formRecherche">
                    <input type="hidden" name="action" value="rechercher_factures">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr auto;gap:14px;align-items:end;">

                        <!-- Fournisseur -->
                        <div>
                            <label class="form-label-dark">Fournisseur</label>
                            <select name="fournisseur" id="sel_fournisseur" class="ctrl-dark">
                                <option value="">Choisissez...</option>
                                <?php foreach ($listeFournisseurs as $f): ?>
                                    <option value="<?= $f->getId() ?>"
                                        <?= ($searchInputs['fournisseur'] ?? '') == $f->getId() ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f->getnom_Fournisseur()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Contrat -->
                        <div>
                            <label class="form-label-dark">Contrat</label>
                            <select name="contrat" id="sel_contrat" class="ctrl-dark" disabled>
                                <option value="">(Fournisseur requis)</option>
                            </select>
                        </div>

                        <!-- N° Enregistrement -->
                        <div>
                            <label class="form-label-dark">N° Enregistrement</label>
                            <input type="text" name="num_enregistrement" class="ctrl-dark"
                                placeholder="Entrez..."
                                value="<?= htmlspecialchars($searchInputs['num_enregistrement'] ?? '') ?>">
                        </div>

                        <!-- N° Facture -->
                        <div>
                            <label class="form-label-dark">N° Facture</label>
                            <input type="text" name="num_facture" class="ctrl-dark"
                                placeholder="Entrez..."
                                value="<?= htmlspecialchars($searchInputs['num_facture'] ?? '') ?>">
                        </div>

                        <!-- Montant -->
                        <div>
                            <label class="form-label-dark">Montant</label>
                            <input type="text" name="montant" class="ctrl-dark"
                                placeholder="Entrez..."
                                value="<?= htmlspecialchars($searchInputs['montant'] ?? '') ?>">
                        </div>

                        <!-- Bouton -->
                        <div>
                            <button type="submit" class="btn-search">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                                </svg>
                                Chercher Factures
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <!-- Résultats -->
        <div class="card-dark">
            <div style="padding:14px 20px;border-bottom:1px solid #1f2327;">
                <span style="font-size:.84rem;font-weight:600;color:#8b949e;">Résultats de la recherche</span>
            </div>
            <div style="padding:16px 20px;">
                <table id="tableRechercheFacture" class="table w-100 mb-0">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Date Facture</th>
                            <th>Montant</th>
                            <th>Monnaie</th>
                            <th>N° Contrat</th>
                            <th>Fournisseur</th>
                            <th>Structure</th>
                            <th>Statut Actuel</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($searchResults)): ?>
                            <?php foreach ($searchResults as $r): ?>
                                <?php
                                $sl = $r['statut_actuel'] ?? 'RECU';
                                $badgeSl = match (true) {
                                    str_contains(strtolower($sl), 'traitement') => 'badge-recu',
                                    str_contains(strtolower($sl), 'pay')        => 'badge-paye',
                                    str_contains(strtolower($sl), 'rejet')      => 'badge-rejeter',
                                    str_contains(strtolower($sl), 'affect')     => 'badge-affected',
                                    default => 'badge-default-s'
                                };
                                ?>
                                <tr>
                                    <td><span class="badge-num"><?= htmlspecialchars($r['Num_facture']) ?></span></td>
                                    <td class="text-muted small"><?= !empty($r['date_facture']) ? date('d/m/Y', strtotime($r['date_facture'])) : '—' ?></td>
                                    <td class="fw-bold"><?= number_format(floatval($r['Montant']), 2, '.', ' ') ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($r['monnaie'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['num_Contrat'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['Nom_Fournisseur'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['structure'] ?? '—') ?></td>
                                    <td><span class="<?= $badgeSl ?>"><?= htmlspecialchars($sl) ?></span></td>
                                    <td class="text-center">
                                        <form method="POST" action="../../Controllers/Facture/FactureController.php">
                                            <input type="hidden" name="action" value="view_details">
                                            <input type="hidden" name="facture_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn-view">Afficher<br>détail</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
         <?php include '../Includes/footer.php'; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';

        $('#tableRechercheFacture').DataTable({
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            order: [
                [1, 'desc']
            ],
            language: {
                search: 'Rechercher :',
                emptyTable: 'Sélectionnez vos critères et cliquez sur Chercher.',
                zeroRecords: 'Aucun résultat correspondant',
                info: 'Affichage de _START_ à _END_ sur _TOTAL_ entrées',
                infoEmpty: 'Affichage de 0 à 0 sur 0 entrées',
                paginate: {
                    previous: 'Précédente',
                    next: 'Suivante'
                },
                lengthMenu: 'Afficher _MENU_ entrées'
            },
            columnDefs: [{
                orderable: false,
                targets: [8]
            }]
        });

        // AJAX load contrats on fournisseur change
        $('#sel_fournisseur').on('change', function() {
            const fid = $(this).val();
            const sel = $('#sel_contrat');

            if (!fid) {
                sel.html('<option value="">(Fournisseur requis)</option>').prop('disabled', true);
                return;
            }

            sel.prop('disabled', false).html('<option>Chargement...</option>');

            fetch('../../Controllers/LOCAL_API/Factures/get_contrats.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        fournisseur_id: fid
                    })
                })
                .then(r => r.json())
                .then(data => {
                    let html = '<option value="">Tous les contrats</option>';
                    if (data && data.length > 0) {
                        data.forEach(c => {
                            const num = c.num_Contrat || c.num_contrat || '';
                            const selected = '<?= $searchInputs["contrat"] ?? "" ?>' == c.id ? 'selected' : '';
                            html += `<option value="${c.id}" ${selected}>${num}</option>`;
                        });
                    }
                    sel.html(html);
                })
                .catch(() => sel.html('<option value="">Erreur</option>'));
        });

        // Restore contrat if search was submitted
        <?php if (!empty($searchInputs['fournisseur'])): ?>
            $('#sel_fournisseur').val('<?= $searchInputs['fournisseur'] ?>').trigger('change');
        <?php endif; ?>
    });
</script>