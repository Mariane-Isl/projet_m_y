<?php
session_start();
require_once '../../classes/Database.php';
require_once '../../classes/region.php';

$database = new Database();
$db       = $database->getConnection();
$regions  = Region::getAll($db);

$page_title = "Gestion des Régions";
?>
<?php include '../Includes/header.php'; ?>
<?php include '../Includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="content-area">

        <!-- Messages flash -->
        <?php if (isset($_SESSION['flash_message'])): ?>
        <?php
    $swal_icon  = ($_SESSION['flash_type'] === 'danger') ? 'error' : $_SESSION['flash_type'];
    $swal_title = ($_SESSION['flash_type'] === 'success') ? 'Opération réussie' : 'Attention';
    ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $swal_icon ?>',
                title: '<?= $swal_title ?>',
                text: '<?= addslashes($_SESSION['flash_message']) ?>',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b9eff',
                timer: 4000,
                timerProgressBar: true
            });
        });
        </script>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <div class="section-card mt-3">
            <div class="section-header">
                <h3 class="section-title">Liste des Régions DP</h3>
                <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addRegionModal"
                    style="border:none;cursor:pointer;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                    </svg>
                    Nouvelle Région
                </button>
            </div>

            <div class="table-responsive">
                <table class="factures-table">
                    <thead>
                        <tr>
                            <th style="width:10%;">ID</th>
                            <th style="width:22%;">Code Région</th>
                            <th>Nom de la Région</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($regions)): ?>
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                                    </svg>
                                    <p>Aucune région trouvée.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($regions as $region): ?>
                        <tr>
                            <td style="color:var(--text-muted);">#<?= htmlspecialchars($region->getId()) ?></td>
                            <td><span
                                    class="badge-status badge-process"><?= htmlspecialchars($region->getCode()) ?></span>
                            </td>
                            <td><strong
                                    style="color:var(--text-main);"><?= htmlspecialchars($region->getLabel()) ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /content-area -->

    <!-- ════ MODALE : AJOUT RÉGION ════ -->
    <div class="modal fade" id="addRegionModal" tabindex="-1" aria-labelledby="addRegionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addRegionForm" action="../../Controllers/REGIONS/RegionController.php" method="POST"
                    novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRegionModalLabel">Ajouter une Nouvelle Région</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_region">
                        <div class="mb-3">
                            <label class="form-label">Code de la région <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" maxlength="10" placeholder="Ex : RG01"
                                style="text-transform:uppercase;">
                            <div id="code-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom de la région <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label" maxlength="100"
                                placeholder="Ex : Région Centre">
                            <div id="label-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-primary-custom"
                            style="border:none;cursor:pointer;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <?php include '../Includes/footer.php'; ?>
    </div>