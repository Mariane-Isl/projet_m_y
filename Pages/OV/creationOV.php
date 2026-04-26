<?php
session_start();
// 1. Configuration des titres et chemins
$page_title = "Création d'ordre de Virement";

// 2. Inclusions (On inclut le header qui contient le début du HTML et le CSS)
include '../Includes/header.php';
?>
<style>
    /* Custom Alerts pour le Dark Theme */
    .alert-dark-custom {
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        margin-top: 15px;
        display: none;
        /* Caché par défaut */
    }

    .alert-dark-warning {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .alert-dark-info {
        background-color: rgba(0, 195, 255, 0.1);
        color: #00c3ff;
        border: 1px solid rgba(0, 195, 255, 0.3);
    }

    .alert-dark-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: #ff4d4d;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }

    /* Structure globale */
    .app-container {
        display: flex;
        min-height: 100vh;
        background-color: #0b0e11;
    }

    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        /* Évite les débordements de flexbox */
    }

    .content-body {
        padding: 20px;
        flex: 1;
    }

    /* Breadcrumb */
    .breadcrumb-custom {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 5px 0 0 0;
        font-size: 0.8rem;
    }

    .breadcrumb-item a {
        color: #00c3ff;
        text-decoration: none;
    }

    .breadcrumb-item.active {
        color: #a1a5b7;
    }

    .breadcrumb-item+.breadcrumb-item::before {
        content: "›";
        color: #495057;
        padding: 0 8px;
    }

    .page-main-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.6rem;
        margin: 0;
    }

    /* Cards */
    .card-custom {
        background-color: #15191d;
        border: 1px solid #24292d;
        border-radius: 12px;
        overflow: hidden;
    }

    .card-custom-header {
        padding: 15px 20px;
        border-bottom: 1px solid #24292d;
    }

    .card-custom-header h5 {
        margin: 0;
        color: #ffffff;
        font-size: 1rem;
        font-weight: 600;
    }

    .card-custom-body {
        padding: 20px;
    }

    /* Forms */
    .form-label-custom {
        display: block;
        color: #a1a5b7;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .form-select-custom,
    .form-control-custom {
        background-color: #1a1e23;
        border: 1px solid #2d3239;
        color: #ffffff;
        padding: 10px 12px;
        border-radius: 8px;
        width: 100%;
        transition: all 0.3s;
    }

    .form-select-custom:focus {
        border-color: #00c3ff;
        box-shadow: 0 0 0 3px rgba(0, 195, 255, 0.15);
        outline: none;
    }

    /* Buttons */
    .btn-action {
        height: 42px;
        border: none;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        transition: 0.2s;
    }

    .btn-search {
        background-color: #00c3ff;
        color: #000;
        padding: 0 20px;
    }

    .btn-search:hover {
        background-color: #00a0d1;
    }

    .btn-reset {
        background-color: #2d3239;
        color: #fff;
        width: 42px;
    }

    .btn-reset:hover {
        background-color: #3d434d;
    }

    .btn-generate-virement {
        background: linear-gradient(135deg, #00c875, #00a35e);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(0, 200, 117, 0.2);
        transition: 0.3s;
    }

    .btn-generate-virement:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 200, 117, 0.3);
    }

    /* Table */
    .table-dark-modern thead th {
        background-color: #1a1e23;
        color: #5d666d;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border: none;
        padding: 15px;
    }

    .table-dark-modern tbody td {
        background-color: transparent;
        color: #e1e1e1;
        border-bottom: 1px solid #24292d;
        padding: 15px;
        vertical-align: middle;
        font-size: 0.85rem;
    }

    /* Details box */
    .details-box-modern {
        background: rgba(0, 195, 255, 0.05);
        border: 1px solid rgba(0, 195, 255, 0.2);
        border-radius: 10px;
        padding: 15px;
    }

    /* Checkbox custom */
    .form-check-input-custom {
        width: 18px;
        height: 18px;
        background-color: #2d3239;
        border: 1px solid #495057;
        border-radius: 4px;
        cursor: pointer;
    }

    .form-check-input-custom:checked {
        background-color: #00c3ff;
        border-color: #00c3ff;
    }
</style>

<!-- Inclusion de la Sidebar -->
<?php include '../Includes/sidebar.php'; ?>

<!-- Conteneur principal qui contient la Topbar + le Contenu -->
<main class="main-content">

    <!-- Inclusion de la Topbar -->
    <?php include '../Includes/topbar.php'; ?>

    <div class="content-body">
        <div class="container-fluid">

            <!-- Fil d'ariane / Header Page -->
            <div class="page-header-zone mb-4">
                <div class="d-flex flex-column">
                    <h2 class="page-main-title"><?= $page_title ?></h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb-custom">
                            <li class="breadcrumb-item"><a href="#">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="#">Ordres de Virement</a></li>
                            <li class="breadcrumb-item active">Nouveau</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- ZONE FILTRES (CARD) -->
            <div class="card-custom mb-4">
                <div class="card-custom-header">
                    <h5><i class="fas fa-filter me-2"></i>Critères de sélection</h5>
                </div>
                <div class="card-custom-body">
                    <?php
                    require_once '../../Classes/Database.php';
                    require_once '../../Classes/Fournisseur.php';
                    $db = (new Database())->getConnection();
                    $listeFournisseurs = Fournisseur::getAll($db);
                    ?>

                    <form id="filterForm" class="row g-3">
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label-custom">Fournisseur <span class="text-danger">*</span></label>
                            <select class="form-select-custom shadow-none" id="fournisseur_id" name="fournisseur_id">
                                <option value="" selected disabled>Sélectionnez un fournisseur</option>
                                <?php foreach ($listeFournisseurs as $f): ?>
                                    <option value="<?= htmlspecialchars($f->getId()) ?>">
                                        <?= htmlspecialchars($f->getnom_Fournisseur()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label-custom">Contrat <span class="text-danger">*</span></label>
                            <select class="form-select-custom shadow-none" id="contrat_id" name="contrat_id">
                                <option value="" selected disabled>Attente fournisseur...</option>
                            </select>
                        </div>
                        <div id="contrat_info" class="details-box-modern mt-3" style="display: none;"></div>

                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label-custom">Devise</label>
                            <select class="form-select-custom shadow-none" id="devise_id" name="devise_id">
                                <option value="" selected disabled>Chargement...</option>
                            </select>
                        </div>

                        <!-- Bloc Structure (Région) -->
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label-custom">Structure</label>
                            <select class="form-select-custom shadow-none" id="structure_id" name="structure_id">
                                <option value="" selected disabled>Chargement...</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label-custom">Nature OV <span class="text-danger">*</span></label>
                            <select class="form-select-custom shadow-none" id="nature_ov" name="nature_ov">
                                <option value="" selected disabled>Sélectionnez...</option>

                            </select>
                        </div>
                        <!-- Boutons (Conservés tels quels) -->
                        <div class="col-12 col-md-4 col-lg-2 d-flex align-items-end gap-2">
                            <button type="button" id="btnSearch" class="btn-action btn-search flex-grow-1">
                                <i class="fas fa-search me-1"></i> Rechercher
                            </button>

                        </div>
                    </form>
                    <div id="filterMessage" class="alert-dark-custom"></div>
                    <!-- Zone dynamique : Détails du contrat -->
                    <div id="contrat_info" class="details-box-modern mt-3" style="display: none;"></div>
                </div>
            </div>

            <!-- ZONE TABLEAU DES FACTURES -->
            <div class="card-custom">
                <div class="card-custom-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-file-invoice-dollar me-2"></i>Factures éligibles au virement</h5>
                    <span class="badge bg-primary" id="selectedCount">0 sélectionnée(s)</span>
                </div>
                <div class="card-custom-body">
                    <div class="table-responsive">
                        <table class="table table-dark-modern w-100" id="virementTable">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" class="form-check-input-custom"
                                            id="selectAll"></th>
                                    <th>N° Facture</th>
                                    <th>Bordereau</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Devise</th>
                                    <th>Fournisseur</th>
                                    <th>Statut</th>

                                </tr>
                            </thead>
                            <tbody>
                                <!-- Chargé par AJAX ou PHP -->
                            </tbody>
                        </table>
                    </div>

                    <div class="footer-actions mt-4 d-flex justify-content-end">
                        <button type="button" id="btnGenererVirement" class="btn-generate-virement">
                            <i class="fas fa-check-circle me-2"></i> Générer l'ordre de virement
                        </button>
                    </div>
                </div>
            </div>

        </div> <!-- /container-fluid -->
        <?php include '../Includes/footer.php'; ?>
    </div> <!-- /content-body -->

    <!-- Inclusion du Footer -->
</main>

<!-- Styles spécifiques pour l'harmonie avec le dashboard -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {

        // ==========================================
        // 1. INITIALISATION DE LA DATATABLE (Une seule fois !)
        // ==========================================
        var table = $('#virementTable').DataTable({
            language: {
                "sEmptyTable": "Aucune donnée disponible dans le tableau",
                "sInfo": "Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",
                "sInfoEmpty": "Affichage de l'élément 0 à 0 sur 0 élément",
                "sInfoFiltered": "(filtré à partir de _MAX_ éléments au total)",
                "sInfoPostFix": "",
                "sInfoThousands": ",",
                "sLengthMenu": "Afficher _MENU_ éléments",
                "sLoadingRecords": "Chargement...",
                "sProcessing": "Traitement...",
                "sSearch": "Rechercher :",
                "sZeroRecords": "Aucun élément correspondant trouvé",
                "oPaginate": {
                    "sFirst": "Premier",
                    "sLast": "Dernier",
                    "sNext": "Suivant",
                    "sPrevious": "Précédent"
                },
                "oAria": {
                    "sSortAscending": ": activer pour trier la colonne par ordre croissant",
                    "sSortDescending": ": activer pour trier la colonne par ordre décroissant"
                },
                "select": {
                    "rows": {
                        "_": "%d lignes sélectionnées",
                        "0": "Aucune ligne sélectionnée",
                        "1": "1 ligne sélectionnée"
                    }
                }
            },
            dom: 'rtip',
            pageLength: 10,
            ordering: false,
            destroy: true
        });


        // ==========================================
        // 2. CHARGEMENT DYNAMIQUE (Devises & Régions)
        // ==========================================

        // Devises
        $.ajax({
            url: '../../Controllers/LOCAL_API/Monnaies/get_monnaies.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let $selectDevise = $('#devise_id');
                $selectDevise.empty();
                if (response.success && response.data && response.data.length > 0) {
                    $selectDevise.append('<option value="" selected disabled>Sélectionnez...</option>');
                    response.data.forEach(function(monnaie) {
                        let isSelected = (monnaie.code === '') ? 'selected' : '';
                        $selectDevise.append(
                            `<option value="${monnaie.code}" ${isSelected}>${monnaie.code} - ${monnaie.label}</option>`
                        );
                    });
                } else {
                    $selectDevise.append('<option value="" disabled>Aucune devise</option>');
                }
            },
            error: function() {
                $('#devise_id').html('<option value="" disabled>Erreur API Monnaies</option>');
            }
        });

        // Structures (Régions)
        $.ajax({
            url: '../../Controllers/LOCAL_API/List_region/get_regions.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let $selectStructure = $('#structure_id');
                $selectStructure.empty();
                if (response.success && response.data.length > 0) {
                    $selectStructure.append(
                        '<option value="" selected disabled>Sélectionnez...</option>');
                    response.data.forEach(function(region) {
                        $selectStructure.append(
                            `<option value="${region.id}">${region.code} - ${region.label}</option>`
                        );
                    });
                } else {
                    $selectStructure.append('<option value="" disabled>Aucune structure</option>');
                }
            },
            error: function() {
                $('#structure_id').html('<option value="" disabled>Erreur API Régions</option>');
            }
        });


        // ==========================================
        // 3. CASCADE : FOURNISSEUR -> CONTRAT
        // ==========================================
        $('#fournisseur_id').on('change', function() {
            const id = $(this).val();
            const $contratSelect = $('#contrat_id');

            $contratSelect.html('<option>Chargement...</option>');
            $('#contrat_info').hide();

            $.ajax({
                url: '../../Controllers/LOCAL_API/Contrat/get_contrats_by_fournisseur.php',
                type: 'POST',
                data: {
                    fournisseur_id: id
                },
                dataType: 'json',
                success: function(data) {
                    $contratSelect.html(
                        '<option value="" selected disabled>Choisissez le contrat</option>');
                    if (data && data.length > 0) {
                        data.forEach(c => {
                            $contratSelect.append(
                                `<option value="${c.id}">${c.num_contrat}</option>`);
                        });
                    } else {
                        $contratSelect.html(
                            '<option value="" disabled>Aucun contrat trouvé</option>');
                    }
                }
            });
        });




        // ==========================================
        // 4. BOUTON RECHERCHE -> CHARGER FACTURES
        // ==========================================
        $('#btnSearch').on('click', function() {
            let fournisseur = $('#fournisseur_id').val();
            let contrat = $('#contrat_id').val();
            let devise = $('#devise_id').val();
            let structure = $('#structure_id').val();
            let $msgBox = $('#filterMessage');

            $msgBox.hide().removeClass('alert-dark-warning alert-dark-info alert-dark-danger');

            // INDEPENDENT CHECKS
            switch (true) {
                // 1. Si AUCUN filtre n'est sélectionné (Le cas général)
                case (!fournisseur && !contrat && !devise && !structure):
                    $msgBox.html(
                        '<i class="fas fa-exclamation-triangle me-2"></i> Veuillez <strong>sélectionner les filtres de recherche</strong>.'
                    ).addClass('alert-dark-warning').fadeIn();
                    return;

                    // 2. Vérifications individuelles
                case (!fournisseur):
                    $msgBox.html(
                        '<i class="fas fa-exclamation-triangle me-2"></i> Sélectionnez un <strong>fournisseur</strong>.'
                    ).addClass('alert-dark-warning').fadeIn();
                    return;

                case (!contrat):
                    $msgBox.html(
                        '<i class="fas fa-exclamation-triangle me-2"></i> Sélectionnez un <strong>contrat</strong>.'
                    ).addClass('alert-dark-warning').fadeIn();
                    return;

                case (!devise):
                    $msgBox.html(
                        '<i class="fas fa-exclamation-triangle me-2"></i> Sélectionnez une <strong>devise</strong>.'
                    ).addClass('alert-dark-warning').fadeIn();
                    return;

                case (!structure):
                    $msgBox.html(
                        '<i class="fas fa-exclamation-triangle me-2"></i> Sélectionnez une <strong>structure</strong>.'
                    ).addClass('alert-dark-warning').fadeIn();
                    return;

                    // 3. Par défaut : Tout est bien sélectionné, on continue !
                default:
                    break;
            }

            // Loader logic
            let $btn = $(this);
            let originalText = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Recherche...').prop(
                'disabled', true);

            $.ajax({
                url: '../../Controllers/LOCAL_API/Factures/getFiltredFacture.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    fournisseur_id: fournisseur,
                    contrat_id: contrat,
                    devise_id: devise,
                    structure_id: structure
                },
                success: function(response) {
                    $btn.html(originalText).prop('disabled', false);

                    if (response.success) {
                        table.clear();

                        // SAFTEY CHECK: Added "!response.data" to prevent JS from crashing if PHP returns null
                        if (!response.data || response.data.length === 0) {
                            $msgBox.html(
                                `<i class="fas fa-info-circle me-2"></i> Aucune facture trouvée pour ces critères .`
                            ).addClass('alert-dark-info').fadeIn();
                        } else {
                            // YOUR EXACT LOGIC
                            response.data.forEach(function(f) {
                                table.row.add([
                                    `<input type="checkbox" class="form-check-input-custom row-checkbox" value="${f.Num_facture}" data-id="${f.id}">`,
                                    `<strong>${f.Num_facture}</strong>`,
                                    f.Num_Bordereaux,
                                    f.date_facture,
                                    `<span class="text-success fw-bold">${parseFloat(f.montant_total).toLocaleString('fr-DZ')}</span>`,
                                    f.devise,
                                    f.fournisseur,
                                    `<span class="badge bg-warning text-dark">${f.statut}</span>`,
                                    `<button class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></button>`
                                ]);
                            });
                        }
                        table.draw();
                    } else {
                        $msgBox.html('<i class="fas fa-times-circle me-2"></i> ' +
                                response.message).addClass('alert-dark-danger')
                            .fadeIn();
                    }
                },
                error: function(xhr) {
                    // SAFETY CHECK: If PHP fails, reset the button and log the error in the console (F12)
                    $btn.html(originalText).prop('disabled', false);
                    console.error("Server Error:", xhr.responseText);
                }
            });
        });

        // ==========================================
        // 5. GESTION DES CHECKBOXES (Sélection)
        // ==========================================

        // Clic sur "Tout sélectionner"
        $('#selectAll').on('click', function() {
            var rows = table.rows({
                'search': 'applied'
            }).nodes();
            $('input[type="checkbox"].row-checkbox', rows).prop('checked', this.checked);
            updateCounter();
        });

        // Clic sur une case individuelle (Délégation d'événement car les lignes sont dynamiques)
        $('#virementTable tbody').on('change', '.row-checkbox', function() {
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            }
            updateCounter();
        });

        function updateCounter() {
            // Compte correctement sur toutes les pages du tableau
            var count = table.$('.row-checkbox:checked').length;
            $('#selectedCount').text(count + ' sélectionnée(s)');
        }

        // ==========================================
        // 6. ENVOI A L'API PUIS REDIRECTION VIA POST
        // ==========================================
        $('#btnGenererVirement').on('click', function() {
            var selectedRows = table.$('.row-checkbox:checked').closest('tr');
            let $msgBox = $('#filterMessage');

            // 1. NOUVELLE VÉRIFICATION : Nature OV sélectionnée ?
            if (!$('#nature_ov').val()) {
                $msgBox.html(
                        '<i class="fas fa-exclamation-triangle me-2"></i> Veuillez sélectionner la <strong>Nature OV</strong> avant de générer le virement.'
                    )
                    .removeClass('alert-dark-info alert-dark-danger').addClass('alert-dark-warning')
                    .fadeIn();

                // Optionnel : Focus sur le champ pour aider l'utilisateur
                $('#nature_ov').focus();
                return;
            }

            // 2. VÉRIFICATION : Factures sélectionnées ?
            if (selectedRows.length === 0) {
                $msgBox.html(
                        '<i class="fas fa-exclamation-triangle me-2"></i> Veuillez sélectionner au moins une facture.'
                    )
                    .removeClass('alert-dark-info alert-dark-danger').addClass('alert-dark-warning')
                    .fadeIn();
                return;
            }

            let facturesData = [];
            let montantTotal = 0;

            // Extraction des données du tableau
            selectedRows.each(function() {
                let rowData = table.row(this).data();
                let idFacture = $(this).find('.row-checkbox').data('id');
                let numFacture = $(this).find('.row-checkbox').val();
                let rawMontant = $(rowData[4]).text();
                let montantClean = parseFloat(rawMontant.replace(/\s/g, '').replace(',', '.'));

                montantTotal += montantClean;

                facturesData.push({
                    idFacture: idFacture,
                    num_facture: numFacture,
                    date: rowData[3],
                    montant: rawMontant,
                    devise: rowData[5],
                    structure: $("#structure_id option:selected").text().split(' - ')[0]
                });
            });

            // Données de l'en-tête (Filtres)
            let infoHeader = {
                fournisseur_id: $("#fournisseur_id").val(),
                fournisseur: $("#fournisseur_id option:selected").text(),

                structure_id: $("#structure_id").val(),
                structure: $("#structure_id option:selected").text(),

                contrat_id: $("#contrat_id").val(), // <-- AJOUT TRÈS IMPORTANT
                contrat: $("#contrat_id option:selected").text(),

                devise_id: $("#devise_id").val(),
                devise: $("#devise_id option:selected").text(),

                nature_ov_id: $("#nature_ov").val(), // <-- CORRIGÉ (.vale() n'existe pas)
                nature_ov: $("#nature_ov option:selected").text(),

                montant_total: montantTotal.toLocaleString('fr-DZ', {
                    minimumFractionDigits: 2
                })
            };

            let $btn = $(this);
            let originalText = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Traitement...').prop('disabled', true);

            // On cache les messages d'erreur précédents
            $msgBox.hide();

            // 1. ENVOI A L'API VIA POST
            $.ajax({
                url: '../../Controllers/LOCAL_API/OV/prevalidate.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    header_info: infoHeader,
                    factures_list: facturesData
                },
                success: function(response) {
                    if (response.success) {

                        // 2. SI L'API DIT OK -> ON CRÉE UN FORMULAIRE CACHÉ ET ON POST VERS LA VUE
                        let $form = $('<form>', {
                            action: 'pre_validation.php', // Le nom de votre nouvelle page
                            method: 'POST'
                        });

                        // On injecte les données dans le formulaire
                        $form.append($('<input>', {
                            type: 'hidden',
                            name: 'header_info',
                            value: JSON.stringify(infoHeader)
                        }));
                        $form.append($('<input>', {
                            type: 'hidden',
                            name: 'factures_list',
                            value: JSON.stringify(facturesData)
                        }));

                        // Si votre API renvoie un ID de brouillon, on peut l'ajouter
                        if (response.draft_id) {
                            $form.append($('<input>', {
                                type: 'hidden',
                                name: 'draft_id',
                                value: response.draft_id
                            }));
                        }

                        // On soumet le formulaire (cela change de page en POST)
                        $('body').append($form);
                        $form.submit();

                    } else {
                        $btn.html(originalText).prop('disabled', false);
                        $msgBox.html('<i class="fas fa-times-circle me-2"></i> ' + response
                            .message).removeClass('alert-dark-warning').addClass(
                            'alert-dark-danger').fadeIn();
                    }
                },
                error: function(xhr) {
                    $btn.html(originalText).prop('disabled', false);
                    console.error("API Error: ", xhr.responseText);
                    $msgBox.html(
                        '<i class="fas fa-times-circle me-2"></i> Erreur de communication avec l\'API.'
                    ).removeClass('alert-dark-warning alert-dark-info').addClass(
                        'alert-dark-danger').fadeIn();
                }
            });
        });

    });
    // ==========================================
    // Chargement dynamique de la "Nature OV"
    // ==========================================
    $.ajax({
        url: '../../Controllers/LOCAL_API/OV/get_natures.php', // Chemin vers votre nouvelle API
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let $selectNature = $('#nature_ov');
            $selectNature.empty(); // On vide le select

            if (response.success && response.data.length > 0) {
                // Option par défaut
                $selectNature.append('<option value="" selected disabled>Sélectionnez...</option>');

                // Remplissage avec les données de la base
                response.data.forEach(function(nature) {
                    $selectNature.append(
                        `<option value="${nature.code}"> ${nature.label}</option>`
                    );
                });
            } else {
                $selectNature.append('<option value="" disabled>Aucune nature disponible</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Erreur API Nature OV:", error);
            $('#nature_ov').html('<option value="" disabled>Erreur de chargement</option>');
        }
    });
</script>