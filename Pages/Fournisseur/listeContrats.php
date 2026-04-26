<?php
session_start();
require_once '../../classes/Database.php';
require_once '../../classes/Fournisseur.php';
require_once '../../classes/Contrat.php';
require_once '../../classes/utilisateur.php';

/* Récupération de l'ID fournisseur */
if (isset($_POST['fournisseur_id'])) {
    $_SESSION['fournisseur_id'] = intval($_POST['fournisseur_id']);
}
$fournisseur_id = intval($_SESSION['fournisseur_id'] ?? 0);
if ($fournisseur_id <= 0) {
    header('Location: listeFournisseur.php');
    exit;
}

$database           = new Database();
$db                 = $database->getConnection();
$fournisseur        = Fournisseur::getById($db, $fournisseur_id);
$contrats           = Contrat::getByFournisseur($db, $fournisseur_id);
$utilisateursActifs = Utilisateur::getAllActive($db);
$queryPays = 'SELECT code, label FROM paye ORDER BY label ASC';
$stmtPays  = $db->prepare($queryPays);
$stmtPays->execute();
$listePays = $stmtPays->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Contrats — ' . htmlspecialchars($fournisseur->getnom_Fournisseur());
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

        <!-- Fil d'Ariane -->
        <div class="breadcrumb-custom mt-2">
            <a href="../dashboard.php">Accueil</a>
            <span class="sep">/</span>
            <a href="listeFournisseur.php">Fournisseurs</a>
            <span class="sep">/</span>
            <span class="current"><?= htmlspecialchars($fournisseur->getnom_Fournisseur()) ?></span>
        </div>

        <!-- Carte info fournisseur -->
        <div class="detail-card" style="display:flex;align-items:center;gap:14px;padding:16px 22px;margin-bottom:20px;">
            <span class="badge-status badge-process" style="font-size:0.85rem;">
                <?= htmlspecialchars($fournisseur->getCode()) ?>
            </span>
            <strong style="font-size:1rem;color:var(--text-main);">
                <?= htmlspecialchars($fournisseur->getnom_Fournisseur()) ?>
            </strong>
            <span style="color:var(--text-muted);font-size:0.83rem;margin-left:auto;">
                <a href="listeFournisseur.php" style="color:var(--accent-blue);font-size:0.8rem;">
                    ← Retour à la liste
                </a>
            </span>
        </div>

        <!-- Tableau des contrats -->
        <div class="section-card">
            <div class="section-header">
                <h3 class="section-title">Liste des Contrats</h3>
                <button type="button" class="btn-primary-custom" data-bs-toggle="modal"
                    data-bs-target="#addContratModal" style="border:none;cursor:pointer;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                    </svg>
                    Nouveau Contrat
                </button>
            </div>

            <div class="table-responsive">
                <table class="factures-table">
                    <thead>
                        <tr>
                            <th style="width:8%;">ID</th>
                            <th style="width:22%;">N° Contrat</th>
                            <th>Gestionnaire Affecté</th>
                            <th style="width:22%;text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contrats)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                                    </svg>
                                    <p>Aucun contrat pour ce fournisseur.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($contrats as $contrat): ?>
                        <?php
                    $cid      = $contrat->getId();
                    $stmtAff  = $db->prepare('SELECT u.nom, u.prenom FROM affectation a JOIN utilisateur u ON a.utilisateurid = u.id WHERE a.Contratid = :id LIMIT 1');
                    $stmtAff->bindParam(':id', $cid, PDO::PARAM_INT);
                    $stmtAff->execute();
                    $affectation = $stmtAff->fetch(PDO::FETCH_ASSOC);
                    ?>
                        <tr>
                            <td style="color:var(--text-muted);">#<?= htmlspecialchars($contrat->getId()) ?></td>
                            <td><span
                                    class="badge-status badge-process"><?= htmlspecialchars($contrat->getnum_contrat()) ?></span>
                            </td>
                            <td>
                                <?php if ($affectation): ?>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div
                                        style="width:26px;height:26px;border-radius:50%;background:var(--accent-green);opacity:0.9;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;color:#fff;">
                                        <?= strtoupper(mb_substr($affectation['nom'],0,1).mb_substr($affectation['prenom'],0,1)) ?>
                                    </div>
                                    <span style="color:var(--text-main);font-size:0.84rem;">
                                        <?= htmlspecialchars($affectation['nom'] . ' ' . $affectation['prenom']) ?>
                                    </span>
                                </div>
                                <?php else: ?>
                                <span style="color:var(--text-muted);">— Non affecté</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex;gap:7px;justify-content:center;">
                                    <button type="button" onclick="ouvrirModalEdition(<?= $contrat->getId() ?>)"
                                        class="btn-outline-blue" style="font-size:0.75rem;padding:4px 11px;">
                                        ✏️ Modifier
                                    </button>
                                    <button type="button"
                                        onclick="confirmerSuppression(<?= $contrat->getId() ?>, '<?= htmlspecialchars($contrat->getnum_contrat(), ENT_QUOTES) ?>')"
                                        class="btn-outline-blue"
                                        style="font-size:0.75rem;padding:4px 11px;border-color:var(--accent-red);color:var(--accent-red);">
                                        🗑️ Supprimer
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
        <?php include '../Includes/footer.php'; ?>

    </div><!-- /content-area -->

    <!-- ════ MODALE : AJOUT CONTRAT ════ -->
    <div class="modal fade" id="addContratModal" tabindex="-1" aria-labelledby="addContratModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addContratForm" action="../../Controllers/CONTRATS/ContratController.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addContratModalLabel">Nouveau Contrat</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_contrat">
                        <input type="hidden" name="fournisseur_id" value="<?= htmlspecialchars($fournisseur_id) ?>">
                        <div class="mb-3">
                            <label class="form-label">Numéro de contrat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="num_contrat" name="num_contrat" maxlength="25"
                                placeholder="Ex : C-2025-001" style="text-transform:uppercase;">
                            <small style="color:var(--text-muted);font-size:0.74rem;">Ce numéro doit être
                                unique.</small>
                            <div id="numero-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Affecter à un gestionnaire <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="utilisateur_id" name="utilisateur_id">
                                <option value="" disabled selected>-- Sélectionnez un gestionnaire --</option>
                                <?php foreach ($utilisateursActifs as $userObj): ?>
                                <option value="<?= htmlspecialchars($userObj->getId()) ?>">
                                    <?= htmlspecialchars($userObj->getNom() . ' ' . $userObj->getPrenom()) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="user-error" class="invalid-feedback" style="font-weight:600;"></div>
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
    </div>

    <!-- ════ MODALE : MODIFICATION CONTRAT ════ -->
    <div class="modal fade" id="editContratModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color:var(--accent-blue);">✏️ Modifier le Contrat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
                </div>

                <!-- Indicateur de chargement -->
                <div id="editModalLoader" style="text-align:center;padding:40px;">
                    <div class="spinner-border text-info" role="status"></div>
                    <p style="margin-top:12px;color:var(--text-muted);font-size:0.83rem;">Chargement des données...</p>
                </div>

                <form id="editContratForm" action="../../Controllers/CONTRATS/ContratController.php" method="POST"
                    style="display:none;">
                    <input type="hidden" name="action" value="update_contrat">
                    <input type="hidden" name="contrat_id" id="edit_contrat_id">
                    <input type="hidden" name="fournisseur_id" value="<?= htmlspecialchars($fournisseur_id) ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Numéro de contrat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_num_contrat" name="num_contrat"
                                maxlength="25" style="text-transform:uppercase;">
                            <div id="edit-numero-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gestionnaire Affecté <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_utilisateur_id" name="utilisateur_id">
                                <option value="">-- Sélectionnez un gestionnaire --</option>
                                <?php foreach ($utilisateursActifs as $userObj): ?>
                                <option value="<?= htmlspecialchars($userObj->getId()) ?>">
                                    <?= htmlspecialchars($userObj->getNom() . ' ' . $userObj->getPrenom()) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="edit-user-error" class="invalid-feedback" style="font-weight:600;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" id="btnSubmitEdit" class="btn-orange" style="border:none;cursor:pointer;">
                            💾 Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script>
    /* ── Validation ajout contrat ── */
    document.addEventListener('DOMContentLoaded', function() {
        const addForm = document.getElementById('addContratForm');
        if (addForm) {
            addForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const num = document.getElementById('num_contrat');
                const user = document.getElementById('utilisateur_id');
                let ok = true;
                num.classList.remove('is-invalid');
                user.classList.remove('is-invalid');

                if (!num.value.trim()) {
                    num.classList.add('is-invalid');
                    document.getElementById('numero-error').textContent =
                        'Veuillez saisir un numéro de contrat.';
                    ok = false;
                }
                if (!user.value) {
                    user.classList.add('is-invalid');
                    document.getElementById('user-error').textContent =
                        'Veuillez sélectionner un gestionnaire.';
                    ok = false;
                }

                if (!ok) return;

                try {
                    const res = await fetch(
                        '../../Controllers/LOCAL_API/fournisseurs/check_contrat_numero.php?num=' +
                        encodeURIComponent(num.value.trim()));
                    const data = await res.json();
                    if (data.exists) {
                        num.classList.add('is-invalid');
                        document.getElementById('numero-error').textContent =
                            'Ce numéro de contrat existe déjà.';
                        return;
                    }
                } catch (err) {
                    console.error(err);
                }

                addForm.submit();
            });
        }
    });

    /* ── Ouvrir la modale d'édition avec chargement AJAX ── */
    function ouvrirModalEdition(contratId) {
        const modal = new bootstrap.Modal(document.getElementById('editContratModal'));
        const loader = document.getElementById('editModalLoader');
        const form = document.getElementById('editContratForm');
        loader.style.display = 'block';
        form.style.display = 'none';
        modal.show();

        fetch('../../Controllers/LOCAL_API/fournisseurs/get_contrat_details.php?id=' + contratId)
            .then(r => r.json())
            .then(function(data) {
                document.getElementById('edit_contrat_id').value = data.id;
                document.getElementById('edit_num_contrat').value = data.num_contrat;
                document.getElementById('edit_utilisateur_id').value = data.utilisateur_id || '';
                loader.style.display = 'none';
                form.style.display = 'block';
            })
            .catch(function() {
                loader.innerHTML = '<p style="color:var(--accent-red);padding:20px;">Erreur de chargement.</p>';
            });
    }

    /* ── Validation modale édition ── */
    document.addEventListener('DOMContentLoaded', function() {
        const formEdit = document.getElementById('editContratForm');
        if (!formEdit) return;

        formEdit.addEventListener('submit', function(e) {
            e.preventDefault();
            const num = document.getElementById('edit_num_contrat');
            const user = document.getElementById('edit_utilisateur_id');
            const numErr = document.getElementById('edit-numero-error');
            const userErr = document.getElementById('edit-user-error');
            let ok = true;

            num.classList.remove('is-invalid');
            numErr.textContent = '';
            user.classList.remove('is-invalid');
            userErr.textContent = '';

            if (num.value.trim().length < 3) {
                num.classList.add('is-invalid');
                numErr.textContent = 'Le numéro doit contenir au moins 3 caractères.';
                ok = false;
            }
            if (!user.value) {
                user.classList.add('is-invalid');
                userErr.textContent = 'Veuillez sélectionner un gestionnaire.';
                ok = false;
            }
            if (!ok) return;

            const btn = document.getElementById('btnSubmitEdit');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement...';

            fetch('../../Controllers/LOCAL_API/fournisseurs/update_contrat.php', {
                    method: 'POST',
                    body: new FormData(formEdit)
                })
                .then(r => r.json())
                .then(function(data) {
                    if (data.success) {
                        Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            })
                            .then(() => location.reload());
                    } else {
                        if (data.message && data.message.includes('déjà utilisé')) {
                            num.classList.add('is-invalid');
                            numErr.textContent = data.message;
                        } else {
                            Swal.fire('Erreur', data.message, 'error');
                        }
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(function() {
                    Swal.fire('Erreur', 'Impossible de modifier le contrat.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });

        document.getElementById('edit_num_contrat').addEventListener('input', function() {
            this.classList.remove('is-invalid');
            document.getElementById('edit-numero-error').textContent = '';
        });
    });

    /* ── Suppression avec confirmation ── */
    function confirmerSuppression(contratId, numContrat) {
        Swal.fire({
            title: 'Supprimer ce contrat ?',
            html: 'Le contrat <strong>' + numContrat + '</strong> sera définitivement supprimé.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f05252',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '🗑️ Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then(function(result) {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('contrat_id', contratId);
                fetch('../../Controllers/LOCAL_API/fournisseurs/delete_contrat.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                    icon: 'success',
                                    title: 'Supprimé !',
                                    text: 'Le contrat ' + numContrat + ' a été supprimé.',
                                    timer: 2000,
                                    showConfirmButton: false
                                })
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Erreur', data.message || 'Impossible de supprimer.', 'error');
                        }
                    })
                    .catch(function() {
                        Swal.fire('Erreur', 'Erreur de connexion.', 'error');
                    });
            }
        });
    }
    </script>