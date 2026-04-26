<?php 
session_start();
$page_title = "Chargement Bordereau"; 

include '../Includes/header.php'; 
include '../Includes/sidebar.php'; 
require_once '../../Classes/Database.php';
require_once '../../Classes/Fournisseur.php';

$db = (new Database())->getConnection();
$listeFournisseurs = Fournisseur::getAll($db); 
?>
<?php
    /*
     * Calcul fiable du chemin vers dist/ quel que soit la profondeur de la page.
     * Uniformisation des slashes pour éviter les bugs sur Windows (WAMP/XAMPP).
     */
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
    $docRoot     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    
    // On retire la racine du serveur de la racine du projet pour avoir l'URL de base
    $baseUrl = str_replace($docRoot, '', $projectRoot);
    $baseUrl = rtrim($baseUrl, '/'); // Sécurité pour éviter les doubles slashes
    
    $cssBase = $baseUrl . '/dist/css/';
    $jsBase  = $baseUrl . '/dist/js/';
?>

<!-- Import des styles -->
<link rel="stylesheet" href="<?= $cssBase ?>bootstrap.min.css">
<link rel="stylesheet" href="<?= $cssBase ?>Font_Google.css">
<link rel="stylesheet" href="<?= $cssBase ?>dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="<?= $cssBase ?>dashbord.css">

<!-- Import des scripts -->
<script src="<?= $jsBase ?>jquery-3.7.0.min.js"></script>
<script src="<?= $jsBase ?>bootstrap.bundle.min.js"></script>
<script src="<?= $jsBase ?>sweetalert2@11.js"></script>
<script src="<?= $jsBase ?>jquery.dataTables.min.js"></script>
<script src="<?= $jsBase ?>dataTables.bootstrap5.min.js"></script>
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
    width: 100% !important;
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
</style>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="container-fluid p-4 flex-grow-1">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 style="color: #ffffff; font-weight: 600; font-size: 1.5rem; margin: 0;">Chargement Bordereau</h2>
                <span style="color: #a1a5b7; font-size: 0.85rem;">SIEGE - Division Production</span>
            </div>
            <a href="../../Controllers/LOCAL_API/Bordereaux/download_template.php" class="btn"
                style="background-color: #00c3ff; color: #000; font-weight: bold; border-radius: 8px; padding: 10px 20px;">
                <i class="fas fa-download me-2"></i> Télécharger le Modèle
            </a>
        </div>

        <!-- FORMULAIRE UPLOAD -->
        <div class="card card-regions">
            <div class="card-body p-4">
                <form id="uploadForm">
                    <div class="row g-4 align-items-end">

                        <div class="col-md-3">
                            <label class="form-label-dark">Fournisseur <span class="text-danger">*</span></label>
                            <select class="form-select form-select-dark shadow-none" id="fournisseur_id"
                                name="fournisseur_id">
                                <option value="" selected disabled>Sélectionnez...</option>
                                <?php foreach ($listeFournisseurs as $f): ?>
                                <option value="<?= htmlspecialchars($f->getId()) ?>">
                                    <?= htmlspecialchars($f->getnom_Fournisseur()) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="error_fournisseur_id" class="text-danger mt-1 small"></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-dark">Contrat <span class="text-danger">*</span></label>
                            <select class="form-select form-select-dark shadow-none" id="contrat_id" name="contrat_id">
                                <option value="">Attente fournisseur...</option>
                            </select>
                            <div id="error_contrat_id" class="text-danger mt-1 small"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label-dark">Fichier (XLS, XLSX) <span
                                    class="text-danger">*</span></label>
                            <!-- Retrait de l'attribut 'required' -->
                            <input class="form-control form-control-dark shadow-none" type="file" id="fichier_excel"
                                accept=".xls,.xlsx">
                            <div id="error_fichier_excel" class="text-danger mt-1 small"></div>

                        </div>

                        <div class="col-md-2">
                            <button type="submit" id="btnUpload" class="btn w-100"
                                style="background-color: #00c3ff; color: #000; font-weight: bold; border-radius: 8px; padding: 10px;">
                                <i class="fas fa-search"></i> Analyser
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <!-- TABLEAU DES RÉSULTATS -->
        <div class="card card-regions mt-4" id="tableContainer" style="display: none;">
            <div class="card-body p-4">
                <div class="text-end mb-3">
                    <button type="button" id="btnSaveToDB" class="btn"
                        style="background-color: #00c875; color: #fff; font-weight: bold; border-radius: 8px; padding: 10px 25px; display: none;">
                        <i class="fas fa-save me-2"></i> Enregistrer en Base de données
                    </button>
                </div>
                <h5 style="color: #00c875; font-size: 1rem; margin-bottom: 20px; text-transform: uppercase;">
                    <i class="fas fa-check-circle me-2"></i> Résultat de l'analyse
                </h5>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-custom">
                        <thead style="position: sticky; top: 0; background-color: #15191d; z-index: 1;">
                            <tr style="color: #5d666d; font-size: 0.75rem; text-transform: uppercase;">
                                <th>N° Facture</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Monnaie</th>
                                <th>Statut / Remarques</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include '../Includes/footer.php'; ?>
    </div>
</div>

<script src="../../dist/js/bootstrap.bundle.min.js"></script>
<script src="../../dist/js/sweetalert2@11.js"></script>

<script>
let validFacturesData = [];

// 1. ANALYSER LE FICHIER (AJAX)
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    validFacturesData = [];

    // 1. Réinitialiser les anciens messages d'erreurs textuels
    document.getElementById('error_fournisseur_id').innerHTML = '';
    document.getElementById('error_contrat_id').innerHTML = '';
    document.getElementById('error_fichier_excel').innerHTML = '';

    const fileInput = document.getElementById('fichier_excel');
    const fournisseur = document.getElementById('fournisseur_id').value;
    const contrat = document.getElementById('contrat_id').value;
    const btnUpload = document.getElementById('btnUpload');

    // 2. On prépare les données à envoyer
    let formData = new FormData();
    if (fileInput.files.length > 0) {
        formData.append('fichier_excel', fileInput.files[0]);
    }
    formData.append('fournisseur_id', fournisseur);
    formData.append('contrat_id', contrat);

    // Changement de l'état du bouton
    let originalText = btnUpload.innerHTML;
    btnUpload.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    btnUpload.disabled = true;
    document.getElementById('tableContainer').style.display = 'none';
    document.getElementById('btnSaveToDB').style.display = 'none';

    try {
        let response = await fetch('../../Controllers/LOCAL_API/Bordereaux/upload_and_validate.php', {
            method: 'POST',
            body: formData
        });

        let data = await response.json();

        // A. ERREURS DE CHAMPS (Formulaire vide ou mauvais code Excel)
        // -> Affichage uniquement sous les inputs, SANS SweetAlert
        if (data.field_errors && Object.keys(data.field_errors).length > 0) {

            if (data.field_errors.fournisseur_id) {
                document.getElementById('error_fournisseur_id').innerHTML =
                    `<i class="fas fa-exclamation-triangle"></i> ${data.field_errors.fournisseur_id}`;
            }
            if (data.field_errors.contrat_id) {
                document.getElementById('error_contrat_id').innerHTML =
                    `<i class="fas fa-exclamation-triangle"></i> ${data.field_errors.contrat_id}`;
            }
            if (data.field_errors.fichier_excel) {
                document.getElementById('error_fichier_excel').innerHTML =
                    `<i class="fas fa-exclamation-triangle"></i> ${data.field_errors.fichier_excel}`;
            }

        }
        // B. ERREURS GLOBALES (ex: Excel vide, erreur système)
        else if (data.global_errors && data.global_errors.length > 0) {
            let errorHtml = "<ul style='text-align:left; color:#ff4d4d;'>";
            data.global_errors.forEach(err => {
                errorHtml += `<li>${err}</li>`;
            });
            errorHtml += "</ul>";

            Swal.fire({
                title: 'Erreur fichier',
                html: errorHtml,
                icon: 'error',
                background: '#15191d',
                color: '#fff'
            });
        }
        // C. SUCCÈS (Génération du tableau)
        else if (data.success) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            let hasInvalidRows = false;

            data.processed_data.forEach(row => {
                let tr = document.createElement('tr');
                let statusHtml = '';

                if (row.is_valid) {
                    statusHtml =
                        `<span style="color: #00c875; font-weight:bold;"><i class="fas fa-check-circle"></i> Valide</span>`;
                } else {
                    hasInvalidRows = true;
                    statusHtml = `<span style="color: #ff4d4d; font-weight:bold;"><i class="fas fa-times-circle"></i> Rejeté :</span><br>
                                  <span style="font-size: 0.8rem; color: #ff9999;">${row.errors.join('<br>')}</span>`;
                }

                tr.innerHTML = `
                    <td>${row.num_facture}</td>
                    <td>${row.date_facture}</td>
                    <td style="color: #00c3ff; font-weight: bold;">${row.montant}</td>
                    <td>${row.monnaie}</td>
                    <td>${statusHtml}</td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('tableContainer').style.display = 'block';

            if (hasInvalidRows) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Attention',
                    text: 'Corrigez les erreurs dans l\'Excel avant d\'enregistrer.',
                    background: '#15191d',
                    color: '#fff'
                });
            } else {
                validFacturesData = data.processed_data;
                document.getElementById('btnSaveToDB').style.display = 'inline-block';
                Swal.fire({
                    icon: 'success',
                    title: 'Fichier valide !',
                    text: 'Toutes les factures sont prêtes à être enregistrées.',
                    background: '#15191d',
                    color: '#fff',
                    confirmButtonColor: '#00c3ff'
                });
            }
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur Serveur',
            text: 'Impossible de contacter le serveur.',
            background: '#15191d',
            color: '#fff'
        });
    }

    // On remet le bouton à son état initial
    btnUpload.innerHTML = originalText;
    btnUpload.disabled = false;
});

// 2. ENREGISTRER EN BASE DE DONNÉES (AJAX)
document.getElementById('btnSaveToDB').addEventListener('click', async function() {
    const contratId = document.getElementById('contrat_id').value;

    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
    this.disabled = true;

    // PLUS BESOIN DU GESTIONNAIRE ICI, LE SERVEUR VA LE TROUVER
    const payload = {
        contrat_id: contratId,
        factures: validFacturesData
    };

    try {
        let res = await fetch('../../Controllers/LOCAL_API/Bordereaux/save_bordereau.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        let out = await res.json();

        if (out.success) {
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: out.message,
                background: '#15191d',
                color: '#fff',
                confirmButtonColor: '#00c875'
            }).then(() => {
                // 👇 NOUVEAU CODE : Création et soumission d'un formulaire POST dynamique 👇

                // 1. On crée un élément <form>
                const form = document.createElement('form');
                form.method = 'POST';
                // Adaptez le chemin si besoin, mais s'ils sont dans le même dossier, ceci suffit :
                form.action = 'enregistreBordereaux.php';

                // 2. On crée un input caché pour l'ID du bordereau
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'bordereau_id';
                hiddenInput.value = out.bordereau_id; // L'ID renvoyé par save_bordereau.php

                // 3. On ajoute l'input au form, le form au body, et on envoie !
                form.appendChild(hiddenInput);
                document.body.appendChild(form);
                form.submit();
            });

        } else {
            Swal.fire('Erreur Base de Données', out.message, 'error');
            this.innerHTML = '<i class="fas fa-save me-2"></i> Enregistrer en Base de données';
            this.disabled = false;
        }
    } catch (e) {
        Swal.fire('Erreur Fatale', 'Impossible de contacter le serveur.', 'error');
        this.innerHTML = '<i class="fas fa-save me-2"></i> Enregistrer en Base de données';
        this.disabled = false;
    }
});

// 3. CHARGEMENT DYNAMIQUE DES CONTRATS
document.getElementById('fournisseur_id').addEventListener('change', function() {
    const fournisseurId = this.value;
    const contratSelect = document.getElementById('contrat_id');
    if (!fournisseurId) return;

    let formData = new FormData();
    formData.append('fournisseur_id', fournisseurId);
    contratSelect.innerHTML = '<option>Chargement...</option>';

    fetch('../../Controllers/LOCAL_API/Contrat/get_contrats_by_fournisseur.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            contratSelect.innerHTML = '<option value="" selected disabled>Sélectionnez un contrat</option>';
            if (data.length > 0) {
                data.forEach(c => {
                    contratSelect.innerHTML += `<option value="${c.id}">${c.num_contrat}</option>`;
                });
            } else {
                contratSelect.innerHTML =
                    '<option value="" selected disabled>Aucun contrat trouvé</option>';
            }
        })
        .catch(err => {
            contratSelect.innerHTML = '<option value="" disabled>Erreur</option>';
        });
});
</script>