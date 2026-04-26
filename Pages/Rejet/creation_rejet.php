<?php
session_start();

$page_title = "Création de Notes de Rejet";

// Inclusion du contrôleur MVC (Uniquement pour récupérer les listes de base)
require_once '../../Controllers/Rejet/RejetController.php';
?>

<link rel="stylesheet" type="text/css" href="../../dist/css/bootstrap.min.css">
<script src="../../dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .main-content {
        background-color: #1a1d20 !important;
        min-height: 100vh;
    }

    .card-regions {
        background-color: #ffffff !important;
        border: 1px solid #e3e6f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
    }

    .table-custom {
        color: #5a5c69;
    }

    .table-custom thead th {
        background-color: #f8f9fc;
        color: #4e73df;
        font-size: 0.8rem;
        text-transform: uppercase;
        border-bottom: 2px solid #e3e6f0;
    }

    .table-custom td {
        vertical-align: middle;
        border-bottom: 1px solid #e3e6f0;
    }

    .form-label-custom {
        color: #4e73df;
        font-weight: bold;
        font-size: 0.85rem;
    }

    .badge-reception {
        background-color: #e3ebf6;
        color: #4e73df;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: bold;
    }
</style>

<?php include '../includes/header.php'; ?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

    <?php include '../includes/topbar.php'; ?>

    <div class="content-area p-4">

        <div class="mb-4">
            <h3 style="color: #4e73df; font-weight: bold;">Création de Notes de Rejet</h3>
            <span class="text-muted" style="font-size: 0.85rem;">Accueil / Création Rejet</span>
        </div>

        <div class="card card-regions mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold" style="color: #4e73df;"><i class="fas fa-filter me-2"></i>Filtres de recherche</h6>
            </div>
            <div class="card-body p-4">
                <form id="formRecherche" novalidate>
                    <div class="row g-4 align-items-end">

                        <div class="col-md-3">
                            <label class="form-label-custom">Fournisseur <span class="text-danger">*</span></label>
                            <select class="form-select shadow-sm" id="fournisseur_id" name="fournisseur_id" required>
                                <option value="" selected disabled>Choisir un fournisseur...</option>
                                <?php
                                if (!empty($listeFournisseurs)):
                                    foreach ($listeFournisseurs as $f):
                                        $id_f = is_array($f) ? ($f['id'] ?? '') : $f->getId();
                                        $nom_f = is_array($f) ? ($f['nom_Fournisseur'] ?? '') : $f->getnom_Fournisseur();
                                ?>
                                        <option value="<?= htmlspecialchars($id_f) ?>"><?= htmlspecialchars($nom_f) ?></option>
                                    <?php
                                    endforeach;
                                endif; ?>
                            </select>
                            <div id="error_fournisseur_id" class="text-danger small mt-1 fw-bold"></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-custom">Contrat <span class="text-danger">*</span></label>
                            <select class="form-select shadow-sm" id="contrat_id" name="contrat_id" required>
                                <option value="" selected disabled>Sélectionnez un fournisseur d'abord</option>
                            </select>
                            <div id="error_contrat_id" class="text-danger small mt-1 fw-bold"></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-custom">Structure <span class="text-danger">*</span></label>
                            <select class="form-select shadow-sm" id="structure_id" name="structure_id" required>
                                <option value="" selected disabled>Choisir une structure...</option>
                                <?php
                                if (!empty($listeStructures)):
                                    foreach ($listeStructures as $s):
                                        $id_s = is_array($s) ? ($s['id'] ?? '') : $s->id;
                                        $label_s = is_array($s) ? ($s['label'] ?? '') : $s->label;
                                ?>
                                        <option value="<?= htmlspecialchars($id_s) ?>"><?= htmlspecialchars($label_s) ?></option>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </select>
                            <div id="error_structure_id" class="text-danger small mt-1 fw-bold"></div>
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm" style="font-weight: bold; border-radius: 6px; padding: 10px;">
                                <i class="fas fa-search me-2"></i> Rechercher
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div id="resultZone" style="display: none;">
            
            <form action="../../Controllers/Rejet/RejetController.php" method="POST" id="formCreationRejet">
                <input type="hidden" name="action" value="go_to_confirmation">
                
                <input type="hidden" name="region_id" id="hidden_structure_id" value="">
                <input type="hidden" name="contrat_id" id="hidden_contrat_id" value="">

                <div class="card card-regions">
                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="m-0 font-weight-bold" style="color: #4e73df;">Résultats de la Recherche</h6>
                            <button type="submit" class="btn shadow-sm" style="background-color: #0056b3; color: #fff; font-weight: bold; border-radius: 8px;">
                                <i class="fas fa-plus me-2"></i> Créer un nouveau rejet
                            </button>
                        </div>

                        <div class="mb-3">
                            <button type="button" id="btnExportExcel" class="btn btn-sm" style="background-color: #198754; color: white;"><i class="fas fa-file-excel"></i> Excel</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom text-center w-100" id="tableRejet">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="form-check-input" id="checkAll"></th>
                                        <th>N° facture</th>
                                        <th>Date Facture</th>
                                        <th>Montant</th>
                                        <th>Monnaie</th>
                                        <th>Contrat</th>
                                        <th>Fournisseur</th>
                                        <th>Structure</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <?php include '../includes/footer.php'; ?>

    </div>
</div>

<script src="../../dist/js/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {

        // 1. CHARGEMENT DES CONTRATS
        $('#fournisseur_id').on('change', function() {
            let fournisseurId = $(this).val();
            let contratSelect = $('#contrat_id');
            contratSelect.html('<option>Chargement...</option>');
            $('#error_fournisseur_id').html('');

            if (fournisseurId) {
                $.ajax({
                    url: '../../Controllers/LOCAL_API/Rejet/get_contrats.php', 
                    type: 'POST',
                    data: { fournisseur_id: fournisseurId },
                    dataType: 'json',
                    success: function(data) {
                        contratSelect.html('<option value="" selected disabled>Choisir un contrat</option>');
                        if (Array.isArray(data)) {
                            $.each(data, function(key, value) {
                                contratSelect.append('<option value="' + value.id + '">' + value.num_Contrat + '</option>');
                            });
                        }
                    },
                    error: function() {
                        contratSelect.html('<option value="" selected disabled>Erreur de chargement</option>');
                    }
                });
            }
        });

        $('#contrat_id').on('change', function() { $('#error_contrat_id').html(''); });
        $('#structure_id').on('change', function() { $('#error_structure_id').html(''); });

        // 2. RECHERCHE DES FACTURES
        $('#formRecherche').on('submit', function(e) {
            e.preventDefault();
            let hasError = false;

            if (!$('#fournisseur_id').val()) { $('#error_fournisseur_id').html('<i class="fas fa-exclamation-triangle"></i> Veuillez sélectionner un fournisseur.'); hasError = true; }
            if (!$('#contrat_id').val()) { $('#error_contrat_id').html('<i class="fas fa-exclamation-triangle"></i> Veuillez sélectionner un contrat.'); hasError = true; }
            if (!$('#structure_id').val()) { $('#error_structure_id').html('<i class="fas fa-exclamation-triangle"></i> Veuillez sélectionner une structure.'); hasError = true; }

            if (!hasError) {
                $.ajax({
                    url: '../../Controllers/LOCAL_API/Rejet/search_factures.php',
                    type: 'POST',
                    data: {
                        fournisseur_id: $('#fournisseur_id').val(),
                        contrat_id: $('#contrat_id').val(),
                        structure_id: $('#structure_id').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#hidden_contrat_id').val($('#contrat_id').val());
                            $('#hidden_structure_id').val($('#structure_id').val());

                            if ($.fn.DataTable.isDataTable('#tableRejet')) {
                                $('#tableRejet').DataTable().clear().destroy();
                            }

                            let tbody = $('#tableRejet tbody');
                            tbody.empty();

                            if(response.data.length === 0) {
                                Swal.fire('Info', 'Aucune facture trouvée.', 'info');
                                $('#resultZone').hide();
                                return;
                            }

                            response.data.forEach(function(facture) {
                                let montantFormat = parseFloat(facture.Montant).toLocaleString('fr-FR', { minimumFractionDigits: 2 });
                                let dateObj = new Date(facture.date_facture);
                                let dateFormatee = ('0' + dateObj.getDate()).slice(-2) + '/' + ('0' + (dateObj.getMonth()+1)).slice(-2) + '/' + dateObj.getFullYear();

                                let row = `<tr>
                                    <td><input type="checkbox" name="facture_ids[]" class="form-check-input checkItem" value="${facture.facture_id}"></td>
                                    <td class="fw-bold text-dark">${facture.Num_facture}</td>
                                    <td>${dateFormatee}</td>
                                    <td class="fw-bold" style="color: #4e73df;">${montantFormat}</td>
                                    <td>${facture.monnaie}</td>
                                    <td>${facture.num_Contrat}</td>
                                    <td>${facture.Nom_Fournisseur}</td>
                                    <td>${facture.nom_structure}</td>
                                    <td><span class="badge-reception">${facture.statut}</span></td>
                                </tr>`;
                                tbody.append(row);
                            });

                            $('#resultZone').show();
                            $('#tableRejet').DataTable({
                                language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json" },
                                pageLength: 10,
                                ordering: false
                            });
                        } else {
                            Swal.fire('Erreur', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Erreur', 'Impossible de joindre l\'API.', 'error');
                    }
                });
            }
        });

        // 3. CHECKBOX ET CREATION
        $(document).on('click', '#checkAll', function() {
            $('.checkItem').prop('checked', this.checked);
        });

        $('#formCreationRejet').on('submit', function(e) {
            if ($('.checkItem:checked').length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Attention !',
                    text: 'Veuillez sélectionner au moins une facture.',
                    confirmButtonColor: '#0056b3'
                });
            }
        });

        // 4. EXPORT EXCEL
        $('#btnExportExcel').on('click', function() {
            let fournisseur = $('#fournisseur_id').val();
            let contrat = $('#contrat_id').val();
            let structure = $('#structure_id').val();

            if (fournisseur && contrat && structure) {
                let form = $('<form>', {
                        action: '../../Controllers/LOCAL_API/Rejet/exel.php',
                        method: 'POST'
                    }).append($('<input>', { type: 'hidden', name: 'fournisseur_id', value: fournisseur }))
                    .append($('<input>', { type: 'hidden', name: 'contrat_id', value: contrat }))
                    .append($('<input>', { type: 'hidden', name: 'structure_id', value: structure }));

                $('body').append(form);
                form.submit();
                form.remove();
            } else {
                Swal.fire({ icon: 'error', title: 'Erreur', text: 'Données manquantes.' });
            }
        });

    });
</script>