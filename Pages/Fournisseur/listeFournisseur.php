<?php
session_start();
require_once '../../classes/Database.php';
require_once '../../classes/Fournisseur.php';
require_once '../../classes/paye.php';

$database     = new Database();
$db           = $database->getConnection();
$fournisseurs = Fournisseur::getAllWithPaye($db);
$listeDesPaye = Paye::getAll($db);

$page_title = "Gestion des Fournisseurs";
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
                <h3 class="section-title">Liste des Fournisseurs</h3>
                <button type="button" class="btn-primary-custom" data-bs-toggle="modal"
                    data-bs-target="#addFournisseurModal" style="border:none;cursor:pointer;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                    </svg>
                    Nouveau Fournisseur
                </button>
            </div>

            <div class="table-responsive">
                <table class="factures-table">
                    <thead>
                        <tr>
                            <th style="width:8%;">ID</th>
                            <th style="width:15%;">Code</th>
                            <th>Nom du Fournisseur</th>
                            <th style="width:15%;">Pays</th>
                            <th style="width:22%;text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fournisseurs)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 7V3H2v18h20V7H12z" />
                                    </svg>
                                    <p>Aucun fournisseur enregistré.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($fournisseurs as $f): ?>
                        <tr>
                            <td style="color:var(--text-muted);">#<?= htmlspecialchars($f->getId()) ?></td>
                            <td><span class="badge-status badge-process"><?= htmlspecialchars($f->getCode()) ?></span>
                            </td>
                            <td><strong
                                    style="color:var(--text-main);"><?= htmlspecialchars($f->getnom_Fournisseur()) ?></strong>
                            </td>
                            <td><?= $f->getPaye() ? htmlspecialchars($f->getPaye()->getLabel()) : '—' ?></td>
                            <td style="text-align:center;">
                                <div style="display:flex;gap:7px;justify-content:center;">
                                    <form action="listeContrats.php" method="POST" style="margin:0;">
                                        <input type="hidden" name="fournisseur_id" value="<?= $f->getId() ?>">
                                        <button type="submit" class="btn-outline-blue"
                                            style="font-size:0.75rem;padding:4px 11px;">
                                            📄 Contrats
                                        </button>
                                    </form>
                                    <button type="button"
                                        onclick="ouvrirEditionFournisseur(<?= $f->getId() ?>, '<?= htmlspecialchars($f->getCode(), ENT_QUOTES) ?>', '<?= htmlspecialchars($f->getnom_Fournisseur(), ENT_QUOTES) ?>', '<?= htmlspecialchars($f->getpaye_id(), ENT_QUOTES) ?>')"
                                        class="btn-outline-green" style="font-size:0.75rem;padding:4px 11px;">
                                        ✏️ Modifier
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /content-area -->

    <!-- ════ MODALE : AJOUT FOURNISSEUR ════ -->
    <div class="modal fade" id="addFournisseurModal" tabindex="-1" aria-labelledby="addFournisseurModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addFournisseurForm" action="../../Controllers/FOURNISSEURS/FournisseurController.php"
                    method="POST" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="addFournisseurModalLabel">Nouveau Fournisseur</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_fournisseur">
                        <div class="mb-3">
                            <label class="form-label">Code du fournisseur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" maxlength="10"
                                placeholder="Ex : F001" style="text-transform:uppercase;">
                            <small style="color:var(--text-muted);font-size:0.74rem;">Ce code doit être unique (ex :
                                F001, F002).</small>
                            <div id="code-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom du fournisseur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom_fournisseur" name="nom_fournisseur"
                                maxlength="100" placeholder="Ex : SARL InfoTech">
                            <div id="nom-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pays <span class="text-danger">*</span></label>
                            <select class="form-select" id="paye_id" name="paye_id">
                                <option value="" disabled selected>-- Sélectionnez un pays --</option>
                                <?php foreach ($listeDesPaye as $p): ?>
                                <option value="<?= htmlspecialchars($p->getId()) ?>">
                                    <?= htmlspecialchars($p->getLabel()) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="pays-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" id="btnSubmitAddFourn" class="btn-primary-custom"
                            style="border:none;cursor:pointer;">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ════ MODALE : MODIFICATION FOURNISSEUR ════ -->
    <div class="modal fade" id="editFournisseurModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color:var(--accent-blue);">✏️ Modifier le Fournisseur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
                </div>
                <form id="editFournisseurForm" action="../../Controllers/FOURNISSEURS/FournisseurController.php"
                    method="POST" novalidate>
                    <input type="hidden" name="action" value="update_fournisseur">
                    <input type="hidden" name="fournisseur_id" id="edit_fourn_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Code du fournisseur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_fourn_code" name="code" maxlength="10"
                                style="text-transform:uppercase;">
                            <div id="edit-code-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom du fournisseur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_fourn_nom" name="nom_fournisseur"
                                maxlength="100">
                            <div id="edit-nom-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pays <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_fourn_paye" name="paye_id">
                                <option value="">-- Sélectionnez un pays --</option>
                                <?php foreach ($listeDesPaye as $p): ?>
                                <option value="<?= htmlspecialchars($p->getId()) ?>">
                                    <?= htmlspecialchars($p->getLabel()) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-orange" style="border:none;cursor:pointer;">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div><?php include '../Includes/footer.php'; ?>
    </div>



    <script>
    /* Ouvrir la modale d'édition */
    function ouvrirEditionFournisseur(id, code, nom, payeId) {
        document.getElementById('edit_fourn_id').value = id;
        document.getElementById('edit_fourn_code').value = code;
        document.getElementById('edit_fourn_nom').value = nom;
        document.getElementById('edit_fourn_paye').value = payeId;
        new bootstrap.Modal(document.getElementById('editFournisseurModal')).show();
    }

    /* Validation du formulaire d'ajout */
    document.addEventListener('DOMContentLoaded', function() {
        const addForm = document.getElementById('addFournisseurForm');
        if (addForm) {
            addForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const code = document.getElementById('code');
                const nom = document.getElementById('nom_fournisseur');
                const pays = document.getElementById('paye_id');
                let valid = true;

                [code, nom, pays].forEach(el => el.classList.remove('is-invalid'));

                if (!nom.value.trim()) {
                    nom.classList.add('is-invalid');
                    document.getElementById('nom-error').textContent = 'Le nom est obligatoire.';
                    valid = false;
                }
                if (!pays.value) {
                    pays.classList.add('is-invalid');
                    document.getElementById('pays-error').textContent =
                        'Veuillez sélectionner un pays.';
                    valid = false;
                }
                if (!code.value.trim()) {
                    code.classList.add('is-invalid');
                    document.getElementById('code-error').textContent = 'Le code est obligatoire.';
                    valid = false;
                } else {
                    try {
                        const res = await fetch(
                            '../../Controllers/LOCAL_API/fournisseurs/check_fournisseur_code.php?code=' +
                            encodeURIComponent(code.value.trim()));
                        const data = await res.json();
                        if (data.exists) {
                            code.classList.add('is-invalid');
                            document.getElementById('code-error').textContent =
                                'Ce code existe déjà.';
                            valid = false;
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }

                if (valid) addForm.submit();
            });
        }
    });
    </script>