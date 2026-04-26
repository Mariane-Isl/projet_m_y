<?php
session_start();
$page_title = "Liste des Rejets de Factures";

include '../Includes/header.php';
include '../Includes/sidebar.php';
require_once '../../Classes/Database.php';
require_once '../../Classes/Fournisseur.php';
require_once '../../Classes/Rejet.php'; // Inclusion de la classe Rejet
require_once '../../Controllers/Rejet/RejetController.php';



$db = (new Database())->getConnection();

// Récupération dynamique depuis la base de données via les classes
$listeFournisseurs = Fournisseur::getAll($db);
$listeStructures   = Rejet::getAllRegions($db); // Récupère les régions/villes
$listeStatuts      = Rejet::getAllStatuts($db); // Récupère les statuts
?>



<style>
    body {
        background-color: #0b0e11 !important;
    }

    .main-content {
        background-color: #0b0e11 !important;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        width: 100% !important;
    }

    .container-fluid {
        padding: 30px !important;
    }

    .card-regions {
        background-color: #15191d !important;
        border: 1px solid #24292d !important;
        border-radius: 12px !important;
        margin-bottom: 20px;
    }

    .table-custom td,
    .table-custom th {
        background-color: transparent !important;
        border: none !important;
        border-bottom: 1px solid #1f2327 !important;
        padding: 15px !important;
        color: #ffffff !important;
        vertical-align: middle;
    }

    .form-control-dark,
    .form-select-dark {
        background-color: #212529 !important;
        border: 1px solid #495057 !important;
        color: #fff !important;
        border-radius: 8px;
    }

    .form-control-dark:focus,
    .form-select-dark:focus {
        border-color: #00c3ff !important;
        box-shadow: 0 0 0 0.25rem rgba(0, 195, 255, 0.25) !important;
    }

    .form-label-dark {
        color: #a1a5b7;
        font-size: 0.85rem;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    /* Style pour la pagination DataTables en Dark Mode */
    .page-item.active .page-link {
        background-color: #00c3ff;
        border-color: #00c3ff;
        color: #000;
    }

    .page-link {
        background-color: #212529;
        border-color: #495057;
        color: #a1a5b7;
    }
</style>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Opération réussie',
                    text: '<?= $_SESSION['flash_message'] ?>',
                    confirmButtonColor: '#00c3ff'
                });
            });
        </script>
    <?php
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    endif; ?>

    <div class="container-fluid p-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 style="color: #ffffff; font-weight: 600; font-size: 1.5rem; margin: 0;">Liste des Rejets de Factures</h2>
                <span style="color: #a1a5b7; font-size: 0.85rem;">Accueil / Liste des rejets</span>
            </div>
        </div>

        <div class="card card-regions">
            <div class="card-body p-4">
                <h5 style="color: #00c3ff; font-size: 1rem; margin-bottom: 20px; text-transform: uppercase;">
                    <i class="fas fa-filter me-2"></i> Filtres de recherche
                </h5>
                <form id="filterForm">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-2">
                            <label class="form-label-dark">Fournisseur</label>
                            <select class="form-select form-select-dark shadow-none" id="fournisseur_id" name="fournisseur_id">
                                <option value="Tous" selected>Tous</option>
                                <?php foreach ($listeFournisseurs as $f): ?>
                                    <option value="<?= htmlspecialchars($f->getId()) ?>">
                                        <?= htmlspecialchars($f->getnom_Fournisseur()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label-dark">Contrat</label>
                            <select class="form-select form-select-dark shadow-none" id="contrat_id" name="contrat_id" disabled>
                                <option value="Tous">Tous</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label-dark">Structure</label>
                            <select class="form-select form-select-dark shadow-none" id="structure_id" name="structure_id">
                                <option value="Toutes" selected>Toutes</option>
                                <?php foreach ($listeStructures as $structure): ?>
                                    <option value="<?= htmlspecialchars($structure['id']) ?>">
                                        <?= htmlspecialchars($structure['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-dark">N° de Rejet</label>
                            <input class="form-control form-control-dark shadow-none" type="text" id="num_rejet" name="num_rejet" placeholder="Contient...">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label-dark">Statut</label>
                            <select class="form-select form-select-dark shadow-none" id="statut" name="statut">
                                <option value="Toutes" selected>Toutes</option>
                                <?php foreach ($listeStatuts as $statut): ?>
                                    <option value="<?= htmlspecialchars($statut['label']) ?>">
                                        <?= htmlspecialchars($statut['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-1">
                            <button type="button" id="btnSearch" class="btn w-100 d-flex align-items-center justify-content-center gap-2" style="background-color: #00c3ff; color: #000; font-weight: bold; border-radius: 8px; padding: 10px; white-space: nowrap; font-size: 0.9rem;">
                                <i class="fas fa-search">search</i>
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="card card-regions mt-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 style="color: #00c875; font-size: 1rem; margin: 0; text-transform: uppercase;">
                        <i class="fas fa-list me-2"></i> Liste des Rejets Créés
                    </h5>
                    <a href="creation_rejet.php" class="btn" style="background-color: #0056b3; color: #fff; font-weight: bold; border-radius: 8px;">
                        <i class="fas fa-plus"></i> Nouveau Rejet
                    </a>
                </div>

                <div class="mb-3">
                    <button type="button" id="btnExportExcel" class="btn btn-sm" style="background-color: #198754; color: white;"><i class="fas fa-file-excel"></i> Excel</button>
                    <button class="btn btn-sm" style="background-color: #dc3545; color: white;"><i class="fas fa-file-pdf"></i> PDF</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom w-100" id="tableRejet">
                        <thead>
                            <tr style="color: #5d666d; font-size: 0.75rem; text-transform: uppercase;">
                                <th>N° Rejet</th>
                                <th>Date</th>
                                <th>Fournisseur</th>
                                <th>Contrat</th>
                                <th>Créé par</th>
                                <th>Type Rejet</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php include '../Includes/footer.php'; ?>
    </div>

    <!-- Formulaire invisible pour envoyer l'ID en POST -->
    <form id="formRedirectDetails" action="details_rejet.php" method="POST" style="display:none;">
        <input type="hidden" name="rejet_id" id="hidden_rejet_id">
        <input type="hidden" name="action" value="view_details">
    </form>


</div>

<script>
    function postDetails(id) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../Controllers/Rejet/RejetController.php';

        // On met l'ID
        let inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id';
        inputId.value = id;

        // On dit au contrôleur qu'on veut juste "voir" (il mettra l'ID en session et redirigera)
        let inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'set_details_session'; // Ajoutez ce cas dans votre contrôleur

        form.appendChild(inputId);
        form.appendChild(inputAction);
        document.body.appendChild(form);
        form.submit();
    }

    function supprimerRejet(id, num) {
        Swal.fire({
            title: 'Supprimer le rejet ?',
            html: `Voulez-vous supprimer définitivement le rejet <b>#${num}</b> ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                // Création d'un formulaire POST dynamique
                let form = document.createElement('form');
                form.method = 'POST';
                // CHEMIN CORRIGÉ : Dossier 'Rejet' au singulier
                form.action = '../../Controllers/Rejet/RejetController.php';

                // On envoie l'action
                let actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_rejet';

                // On envoie l'ID en POST (caché)
                let idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function demanderSuppression(id, num) {
        Swal.fire({
            title: 'Supprimer le rejet ?',
            html: `Voulez-vous supprimer définitivement le rejet <b>#${num}</b> ?<br><small class="text-danger">Les factures redeviendront "Affectées".</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#444c56',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.method = 'POST';
                // ON UTILISE LE CONTROLEUR UNIQUE
                form.action = '../../Controllers/Rejet/RejetController.php';

                let inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'delete_rejet';

                let inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id'; // Le contrôleur attend "id" (car il fait intval($_GET['id'] ?? 0))
                inputId.value = id;

                form.appendChild(inputAction);
                form.appendChild(inputId);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }


    $(document).ready(function() {

        // 1. INITIALISATION DE DATATABLES AVEC AJAX
        let tableRejet = $('#tableRejet').DataTable({
            "processing": true,
            "serverSide": false,
            "lengthChange": false, // <--- C'EST CETTE LIGNE QUI SUPPRIME LE SÉLECTEUR "SHOW ENTRIES"
            "ajax": {
                "url": "../../Controllers/LOCAL_API/Rejet/get_rejets.php",
                "type": "POST",
                "data": function(d) {
                    d.fournisseur_id = $('#fournisseur_id').val();
                    d.Contratid = $('#contrat_id').val();
                    d.region_dpid = $('#structure_id').val();
                    d.num_rejet = $('#num_rejet').val();
                    d.statut = $('#statut').val();
                }
            },
            "columns": [{
                     "data": "num_rejet",
                     "render": function(data, type, row) {
                     // La variable 'data' contient DÉJÀ la chaîne complète (ex: SH/DP/REJ/ORA/001) venant de la BDD.
                     // On l'affiche donc directement sans rien rajouter autour.
                     return `<span class="badge-num" style="color:#00c3ff; font-weight:bold;">${data}</span>`;
                    }

                },
                {
                    "data": "date_statut",
                    "render": function(data) {
                        if (!data) return "—";
                        // On affiche juste la date sans l'heure pour que ce soit plus propre
                        return data.split(' ')[0].split('-').reverse().join('/');
                    }
                },
                {
                    "data": "fournisseur",
                    "defaultContent": "Fournisseur inconnu"
                }, // Doit correspondre à l'alias SQL
                {
                    "data": "contrat",
                    "defaultContent": "—"
                },
                {
                    "data": "cree_par",
                    "defaultContent": "Utilisateur inconnu"
                }, // Doit correspondre à l'alias SQL
                {
                    "data": "cause",
                    "defaultContent": "—"
                },
                {
                    "data": "statut_actuel",
                    "render": function(data, type, row) {
                        if (!data) return '<span class="badge bg-secondary">Inconnu</span>';
                        // On teste le CODE (RECUP) pour choisir la couleur
                        let color = (row.statut_code === 'RECUP') ? '#00c875' : '#ffc107';
                        return `<span class="badge" style="background-color: ${color}; color:#000;">${data}</span>`;
                    }
                },
                {
                    "data": "id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
            <div class="d-flex gap-2 justify-content-center">
                <button onclick="postDetails(${data})" class="btn btn-sm btn-info" title="Voir">
                    <i class="fas fa-eye"></i>
                </button>
                <button onclick="supprimerRejet(${data}, '${row.num_rejet}')" class="btn btn-sm btn-danger" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`;
                    }
                }
            ],

        });

        // 2. ACTION SUR LE BOUTON DE RECHERCHE
        $('#btnSearch').on('click', function() {
            tableRejet.ajax.reload();
        });

        // 3. CHARGEMENT DYNAMIQUE DES CONTRATS LORS DU CHOIX DU FOURNISSEUR
        $('#fournisseur_id').on('change', function() {
            const fournisseurId = $(this).val();
            const contratSelect = $('#contrat_id');

            if (fournisseurId === 'Tous' || !fournisseurId) {
                contratSelect.html('<option value="Tous">Tous</option>');
                contratSelect.prop('disabled', true);
                return;
            }

            contratSelect.prop('disabled', false);
            contratSelect.html('<option>Chargement...</option>');

            let formData = new FormData();
            formData.append('fournisseur_id', fournisseurId);

            fetch('../../Controllers/LOCAL_API/Contrat/get_contrats_by_fournisseur.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    contratSelect.html('<option value="Tous" selected>Tous les contrats</option>');
                    if (data.length > 0) {
                        data.forEach(c => {
                            contratSelect.append(`<option value="${c.id}">${c.num_contrat}</option>`);
                        });
                    }
                })
                .catch(err => {
                    contratSelect.html('<option value="Tous" disabled>Erreur</option>');
                });
        });

        $('#btnExportExcel').on('click', function() {
            // On crée un formulaire dynamique invisible pour envoyer les données en POST et forcer le téléchargement
            let form = $('<form>', {
                'method': 'POST',
                'action': '../../Controllers/LOCAL_API/Rejet/export_rejets_excel.php' // Ajustez le chemin selon votre structure
            });

            // On récupère les valeurs des filtres actuels
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'fournisseur_id',
                'value': $('#fournisseur_id').val()
            }));
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'contrat_id',
                'value': $('#contrat_id').val()
            }));
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'structure_id',
                'value': $('#structure_id').val()
            }));
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'num_rejet',
                'value': $('#num_rejet').val()
            }));
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'statut',
                'value': $('#statut').val()
            }));

            // On ajoute le formulaire au body, on le soumet, puis on le supprime
            $('body').append(form);
            form.submit();
            form.remove();
        });
    });
</script>