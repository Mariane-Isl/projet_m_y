<?php
session_start();
$page_title = "Gestion des Monnaies";
require_once '../../Controllers/Monnaies/MonnaieController.php';
?>
<?php include '../Includes/header.php'; ?>
<?php include '../Includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="content-area">

        <!-- Messages flash via SweetAlert -->
        <?php if (isset($_SESSION['alert'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: <?= json_encode($_SESSION['alert']['icon'])  ?>,
                        title: <?= json_encode($_SESSION['alert']['title']) ?>,
                        text: <?= json_encode($_SESSION['alert']['text'])  ?>,
                        confirmButtonColor: '#3b9eff',
                        confirmButtonText: 'OK'
                    });
                });
            </script>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <!-- Nettoyage des paramètres GET dans l'URL -->
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        </script>

        <div class="section-card mt-3">
            <div class="section-header">
                <h3 class="section-title">Liste des Monnaies</h3>
                <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalAddMoney"
                    style="border:none;cursor:pointer;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                    </svg>
                    Nouvelle Monnaie
                </button>
            </div>

            <div class="table-responsive">
                <table class="factures-table">
                    <thead>
                        <tr>
                            <th style="width:8%;">N°</th>
                            <th style="width:28%;">Code Monnaie</th>
                            <th>Désignation</th>
                            <th style="width:18%;text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allMonnaies)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85z" />
                                        </svg>
                                        <p>Aucune monnaie enregistrée.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1;
                            foreach ($allMonnaies as $m): ?>
                                <tr>
                                    <td style="color:var(--text-muted);">#<?= $i++ ?></td>
                                    <td><span class="badge-status badge-process"><?= htmlspecialchars($m['code']) ?></span></td>
                                    <td style="color:var(--text-main);font-weight:500;"><?= htmlspecialchars($m['label']) ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <div style="display:flex;gap:8px;justify-content:center;">
                                            <button class="btn-outline-blue btn-edit" data-id="<?= $m['id'] ?>"
                                                data-code="<?= htmlspecialchars($m['code'], ENT_QUOTES) ?>"
                                                data-label="<?= htmlspecialchars($m['label'], ENT_QUOTES) ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalEditMoney"
                                                style="font-size:0.75rem;padding:4px 11px;">
                                                ✏️ Modifier
                                            </button>
                                            <a href="?delete_id=<?= $m['id'] ?>"
                                                onclick="return confirm('Confirmer la suppression de cette monnaie ?')"
                                                class="btn-outline-blue"
                                                style="font-size:0.75rem;padding:4px 11px;border-color:var(--accent-red);color:var(--accent-red);">
                                                🗑️ Supprimer
                                            </a>
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

    <!-- ════ MODALE : AJOUT MONNAIE ════ -->
    <div class="modal fade" id="modalAddMoney" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formAddMoney" method="POST" novalidate>
                    <input type="hidden" name="add_money" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter une Monnaie</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Code Monnaie <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="inputCode" class="form-control" placeholder="Ex : DZD"
                                style="text-transform:uppercase;">
                            <div id="error-code" class="invalid-feedback" style="font-weight:600;display:none;">
                                Veuillez renseigner ce champ.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Désignation <span class="text-danger">*</span></label>
                            <input type="text" name="label" id="inputLabel" class="form-control"
                                placeholder="Ex : Dinar Algérien">
                            <div id="error-label" class="invalid-feedback" style="font-weight:600;display:none;">
                                Veuillez renseigner ce champ.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="add_money" class="btn-primary-custom"
                            style="border:none;cursor:pointer;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ════ MODALE : MODIFICATION MONNAIE ════ -->
    <div class="modal fade" id="modalEditMoney" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formEditMoney" method="POST" novalidate>
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="update_money" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la Monnaie</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Code Monnaie <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="edit_code" class="form-control"
                                style="text-transform:uppercase;">
                            <div id="error-edit-code" class="invalid-feedback" style="font-weight:600;display:none;">
                                Ce code monnaie existe déjà.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Désignation <span class="text-danger">*</span></label>
                            <input type="text" name="label" id="edit_label" class="form-control">
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
        </div>
        <?php include '../Includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ── Boutons Modifier : pré-remplissage ── */
            document.querySelectorAll('.btn-edit').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_id').value = this.dataset.id;
                    document.getElementById('edit_code').value = this.dataset.code;
                    document.getElementById('edit_label').value = this.dataset.label;
                    document.getElementById('error-edit-code').style.display = 'none';
                });
            });

            /* ── Formulaire AJOUT ── */
            const formAdd = document.getElementById('formAddMoney');
            if (formAdd) {
                formAdd.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const inputCode = document.getElementById('inputCode');
                    const inputLabel = document.getElementById('inputLabel');
                    const errCode = document.getElementById('error-code');
                    const errLabel = document.getElementById('error-label');
                    let valid = true;

                    errCode.style.display = 'none';
                    errLabel.style.display = 'none';
                    inputCode.classList.remove('is-invalid');
                    inputLabel.classList.remove('is-invalid');

                    if (!inputLabel.value.trim()) {
                        errLabel.innerText = 'Veuillez renseigner ce champ.';
                        errLabel.style.display = 'block';
                        inputLabel.classList.add('is-invalid');
                        valid = false;
                    }
                    if (!inputCode.value.trim()) {
                        errCode.innerText = 'Veuillez renseigner ce champ.';
                        errCode.style.display = 'block';
                        inputCode.classList.add('is-invalid');
                        valid = false;
                    } else {
                        try {
                            const res = await fetch(
                                '../../Controllers/LOCAL_API/Monnaies/check_Monnaie_code.php?code=' +
                                encodeURIComponent(inputCode.value.trim()));
                            const data = await res.json();
                            if (data.exists) {
                                errCode.innerText = 'Ce code monnaie existe déjà.';
                                errCode.style.display = 'block';
                                inputCode.classList.add('is-invalid');
                                valid = false;
                            }
                        } catch (err) {
                            console.error(err);
                        }
                    }

                    if (valid) formAdd.submit();
                });

                /* Reset à la fermeture */
                document.getElementById('modalAddMoney').addEventListener('hidden.bs.modal', function() {
                    formAdd.reset();
                    ['error-code', 'error-label'].forEach(function(id) {
                        const el = document.getElementById(id);
                        if (el) el.style.display = 'none';
                    });
                });
            }

            /* ── Formulaire MODIFICATION ── */
            const formEdit = document.getElementById('formEditMoney');
            if (formEdit) {
                formEdit.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const inputCode = document.getElementById('edit_code');
                    const idValue = document.getElementById('edit_id').value;
                    const errCode = document.getElementById('error-edit-code');
                    errCode.style.display = 'none';
                    inputCode.classList.remove('is-invalid');

                    if (!inputCode.value.trim()) {
                        errCode.innerText = 'Veuillez renseigner ce champ.';
                        errCode.style.display = 'block';
                        inputCode.classList.add('is-invalid');
                        return;
                    }
                    try {
                        const res = await fetch(
                            '../../Controllers/LOCAL_API/Monnaies/check_Monnaie_code.php?code=' +
                            encodeURIComponent(inputCode.value.trim()) + '&id=' + idValue);
                        const data = await res.json();
                        if (data.exists) {
                            errCode.innerText = 'Ce code monnaie existe déjà.';
                            errCode.style.display = 'block';
                            inputCode.classList.add('is-invalid');
                        } else {
                            formEdit.submit();
                        }
                    } catch (err) {
                        console.error(err);
                        formEdit.submit();
                    }
                });
            }
        });
    </script>