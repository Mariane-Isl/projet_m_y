<?php
session_start();
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/utilisateur.php';
require_once __DIR__ . '/../../classes/Role.php';
require_once __DIR__ . '/../../classes/region.php';

$db           = (new Database())->getConnection();
$utilisateurs = Utilisateur::getAll($db);
$allRoles     = Role::getAll($db);
$rolesOptions = Role::getHtmlOptions($allRoles);
$allRegions   = Region::getAll($db);
$regionsOptions = Region::getHtmlOptions($allRegions);

$rolesMap   = [];
foreach ($allRoles   as $r) {
    $rolesMap[$r->getId()]   = $r->getLabel();
}
$regionsMap = [];
foreach ($allRegions as $r) {
    $regionsMap[$r->getId()] = $r->getLabel();
}

$countTotal  = Utilisateur::countAll($db);
$countActive = Utilisateur::countActive($db);

$userToEdit = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['edit_id'])) {
    $_SESSION['edit_user_id'] = (int) $_POST['edit_id'];
    header('Location: Liste_User.php');
    exit;
}
if (!empty($_SESSION['edit_user_id'])) {
    $userToEdit = Utilisateur::getById($db, (int) $_SESSION['edit_user_id']);
    unset($_SESSION['edit_user_id']);
}

$page_title = "Gestion des Utilisateurs";
?>
<?php require_once __DIR__ . '/../Includes/header.php'; ?>
<?php require_once __DIR__ . '/../Includes/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../Includes/topbar.php'; ?>

    <div class="content-area">

        <!-- Messages flash -->
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

        <!-- Cartes statistiques -->
        <div class="stats-row" style="grid-template-columns:repeat(2,1fr);max-width:480px;margin-bottom:24px;">
            <div class="stat-card blue">
                <div class="stat-card-top">
                    <div class="stat-label">Total Utilisateurs</div>
                    <div class="stat-icon-box">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?= $countTotal ?></div>
                <div class="stat-sub">Comptes enregistrés</div>
            </div>
            <div class="stat-card green">
                <div class="stat-card-top">
                    <div class="stat-label">Utilisateurs Actifs</div>
                    <div class="stat-icon-box">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?= $countActive ?></div>
                <div class="stat-sub">Comptes actifs</div>
            </div>
        </div>

        <!-- Tableau des utilisateurs -->
        <div class="section-card">
            <div class="section-header">
                <h3 class="section-title">Liste des Utilisateurs</h3>
                <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal"
                    style="border:none;cursor:pointer;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                    </svg>
                    Nouvel Utilisateur
                </button>
            </div>

            <div class="table-responsive">
                <div style="padding:12px 20px 8px;">
                    <input type="text" id="userSearch" class="form-control"
                        placeholder="🔍  Rechercher un utilisateur..."
                        style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:var(--text-main);border-radius:8px;font-size:0.84rem;max-width:320px;">
                </div>
                <table class="factures-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Nom &amp; Prénom</th>
                            <th>Nom d'utilisateur</th>
                            <th>Rôle</th>
                            <th>Région</th>
                            <th>Statut</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $user): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div
                                        style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--accent-blue),var(--accent-blue-2));display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:#fff;flex-shrink:0;">
                                        <?= strtoupper(mb_substr($user->getNom(), 0, 1) . mb_substr($user->getPrenom(), 0, 1)) ?>
                                    </div>
                                    <strong
                                        style="color:var(--text-main);"><?= htmlspecialchars($user->getNom() . ' ' . $user->getPrenom()) ?></strong>
                                </div>
                            </td>
                            <td style="font-family:monospace;color:var(--accent-blue);font-size:0.83rem;">
                                @<?= htmlspecialchars($user->getUserName()) ?></td>
                            <td>
                                <span class="badge-status badge-process" style="font-size:0.68rem;">
                                    <?= htmlspecialchars($rolesMap[$user->getRoleId()] ?? 'Rôle #' . $user->getRoleId()) ?>
                                </span>
                            </td>
                            <td style="font-size:0.83rem;">
                                <?= htmlspecialchars($regionsMap[$user->getRegionDpId()] ?? '—') ?></td>
                            <td>
                                <?php if ($user->getActive()): ?>
                                <span class="badge-status badge-approved">Actif</span>
                                <?php else: ?>
                                <span class="badge-status badge-rejected">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex;gap:6px;justify-content:center;">
                                    <form method="POST" action="Liste_User.php" style="display:inline;">
                                        <input type="hidden" name="edit_id" value="<?= $user->getId() ?>">
                                        <button type="submit" class="btn-outline-blue"
                                            style="font-size:0.75rem;padding:4px 11px;">
                                            ✏️ Éditer
                                        </button>
                                    </form>
                                    <form method="POST" action="../../Controllers/UserController/toggle_status.php"
                                        style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $user->getId() ?>">
                                        <button type="submit" class="btn-outline-blue"
                                            style="font-size:0.75rem;padding:4px 11px;border-color:<?= $user->getActive() ? 'var(--accent-red)' : 'var(--accent-green)' ?>;color:<?= $user->getActive() ? 'var(--accent-red)' : 'var(--accent-green)' ?>;">
                                            <?= $user->getActive() ? '🔒 Bloquer' : '🔓 Activer' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /content-area -->

    <!-- ════ MODALE : AJOUT UTILISATEUR ════ -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="addUserForm" action="../../Controllers/UserController/addUser.php" method="POST"
                class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Nouvel Utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" id="add_nom" name="nom" class="form-control" placeholder="mark">
                            <div class="invalid-feedback" id="err_nom"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" id="add_prenom" name="prenom" class="form-control"
                                placeholder="zuckerberg">
                            <div class="invalid-feedback" id="err_prenom"></div>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                        <input type="text" id="add_user_name" name="user_name" class="form-control"
                            placeholder="mark.zuckerberg">
                        <div class="invalid-feedback" id="err_username"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="add_password_user" name="password_user" class="form-control"
                            placeholder="••••••••">
                        <div class="invalid-feedback" id="err_password"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="add_confirm_password" name="confirm_password" class="form-control"
                            placeholder="••••••••">
                        <div class="invalid-feedback" id="err_confirm"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select id="add_role_id" name="role_id" class="form-select">
                                <?= $rolesOptions ?>
                            </select>
                            <div class="invalid-feedback" id="err_role"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Région <span class="text-danger">*</span></label>
                            <select id="add_region_dp_id" name="region_dp_id" class="form-select">
                                <?= $regionsOptions ?>
                            </select>
                            <div class="invalid-feedback" id="err_region"></div>
                        </div>
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

    <!-- ════ MODALE : ÉDITION UTILISATEUR ════ -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="../../Controllers/UserController/updateUser.php" method="POST" class="modal-content"
                id="editUserForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserLabel">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role_id" id="edit_role_id" class="form-select">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($allRoles as $role): ?>
                            <option value="<?= $role->getId() ?>"><?= htmlspecialchars($role->getLabel()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select name="active" id="edit_active" class="form-select">
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>

                    <hr class="divider-section" style="margin:16px 0;">
                    <p style="font-size:0.76rem;color:var(--text-muted);margin-bottom:14px;">
                        Pour modifier le mot de passe, renseignez les champs ci-dessous. Laissez vide pour ne pas
                        changer.
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" name="current_password" id="edit_current_password" class="form-control"
                            placeholder="Mot de passe actuel">
                        <div id="edit-current-pwd-error" class="invalid-feedback"
                            style="display:none;font-weight:600;font-size:0.8rem;color:var(--accent-red);margin-top:4px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="new_password" id="edit_new_password" class="form-control"
                            placeholder="Nouveau mot de passe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="edit_confirm_password" name="confirm_password" class="form-control"
                            placeholder="Confirmer le mot de passe">
                        <div id="edit-confirm-pwd-error" class="invalid-feedback"
                            style="display:none;font-weight:600;font-size:0.8rem;color:var(--accent-red);margin-top:4px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-primary-custom"
                        style="border:none;cursor:pointer;">Enregistrer</button>
                </div>
            </form>
        </div>
        <?php require_once __DIR__ . '/../Includes/footer.php'; ?>
    </div>



    <script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ── Recherche dans le tableau ── */
        const searchInput = document.getElementById('userSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('#usersTable tbody tr').forEach(function(row) {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }

        /* ── MODALE AJOUT : validation ── */
        const addForm = document.getElementById('addUserForm');
        const messages = {
            nom: 'Le nom est obligatoire.',
            prenom: 'Le prénom est obligatoire.',
            username: "Le nom d'utilisateur est requis.",
            usernameExists: "Ce nom d'utilisateur est déjà utilisé.",
            password: 'Le mot de passe ne peut pas être vide.',
            confirmEmpty: 'Veuillez confirmer le mot de passe.',
            confirmMismatch: 'Les mots de passe ne correspondent pas.',
            role: 'Veuillez sélectionner un rôle.',
            region: 'Veuillez sélectionner une région.'
        };

        function setError(el, errId, msg) {
            const errDiv = document.getElementById(errId);
            if (msg) {
                el.classList.add('is-invalid');
                if (errDiv) errDiv.textContent = msg;
            } else {
                el.classList.remove('is-invalid');
                if (errDiv) errDiv.textContent = '';
            }
        }

        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                addForm.querySelectorAll('.form-control,.form-select').forEach(el => el.classList
                    .remove('is-invalid'));

                const nom = document.getElementById('add_nom');
                const prenom = document.getElementById('add_prenom');
                const uname = document.getElementById('add_user_name');
                const pwd = document.getElementById('add_password_user');
                const confirm = document.getElementById('add_confirm_password');
                const role = document.getElementById('add_role_id');
                const region = document.getElementById('add_region_dp_id');
                let ok = true;

                if (!nom.value.trim()) {
                    setError(nom, 'err_nom', messages.nom);
                    ok = false;
                }
                if (!prenom.value.trim()) {
                    setError(prenom, 'err_prenom', messages.prenom);
                    ok = false;
                }
                if (!uname.value.trim()) {
                    setError(uname, 'err_username', messages.username);
                    ok = false;
                }
                if (!pwd.value) {
                    setError(pwd, 'err_password', messages.password);
                    ok = false;
                }
                if (!confirm.value) {
                    setError(confirm, 'err_confirm', messages.confirmEmpty);
                    ok = false;
                } else if (pwd.value !== confirm.value) {
                    setError(confirm, 'err_confirm', messages.confirmMismatch);
                    ok = false;
                }
                if (!role.value) {
                    setError(role, 'err_role', messages.role);
                    ok = false;
                }
                if (!region.value) {
                    setError(region, 'err_region', messages.region);
                    ok = false;
                }

                if (!ok) return;

                /* Vérification doublon username */
                const fd = new FormData();
                fd.append('user_name', uname.value);
                fetch('../../Controllers/LOCAL_API/List_User/check_username.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.exists) {
                            setError(uname, 'err_username', messages.usernameExists);
                        } else {
                            const fd2 = new FormData();
                            fd2.append('password_user', pwd.value);
                            fd2.append('confirm_password', confirm.value);
                            fetch('../../Controllers/LOCAL_API/List_User/check_hashed_password.php', {
                                    method: 'POST',
                                    body: fd2
                                })
                                .then(r => r.json())
                                .then(d => {
                                    if (d.valid) addForm.submit();
                                    else setError(confirm, 'err_confirm', d.message);
                                })
                                .catch(() => addForm.submit());
                        }
                    });
            });

            addForm.querySelectorAll('.form-control,.form-select').forEach(el => {
                el.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            });
        }

        /* ── MODALE ÉDITION : validation mot de passe ── */
        const editForm = document.getElementById('editUserForm');
        const editCurPwd = document.getElementById('edit_current_password');
        const editNewPwd = document.getElementById('edit_new_password');
        const editConfirmPwd = document.getElementById('edit_confirm_password');
        const editCurErr = document.getElementById('edit-current-pwd-error');
        const editConfErr = document.getElementById('edit-confirm-pwd-error');

        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                editCurErr.style.display = 'none';
                editCurPwd.classList.remove('is-invalid');
                editConfErr.style.display = 'none';
                editConfirmPwd.classList.remove('is-invalid');

                const userId = document.getElementById('edit_user_id').value;
                const curPwd = editCurPwd.value;
                const newPwd = editNewPwd.value;
                const confirmPwd = editConfirmPwd.value;

                /* Pas de changement de mot de passe → soumettre directement */
                if (!curPwd && !newPwd && !confirmPwd) {
                    editForm.submit();
                    return;
                }

                /* Étape 1 : vérifier le mot de passe actuel */
                const fd1 = new FormData();
                fd1.append('user_id', userId);
                fd1.append('current_password', curPwd);
                fetch('../../Controllers/LOCAL_API/List_User/check_current_password.php', {
                        method: 'POST',
                        body: fd1
                    })
                    .then(r => r.json())
                    .then(function(data) {
                        if (!data.valid) {
                            editCurErr.textContent = data.message ||
                                'Mot de passe actuel incorrect.';
                            editCurErr.style.display = 'block';
                            editCurPwd.classList.add('is-invalid');
                            return;
                        }
                        /* Étape 2 : vérifier nouveau + confirmation */
                        const fd2 = new FormData();
                        fd2.append('password_user', newPwd);
                        fd2.append('confirm_password', confirmPwd);
                        fetch('../../Controllers/LOCAL_API/List_User/check_hashed_password.php', {
                                method: 'POST',
                                body: fd2
                            })
                            .then(r => r.json())
                            .then(function(d) {
                                if (d.valid) {
                                    editForm.submit();
                                } else {
                                    editConfErr.textContent = d.message ||
                                        'Les mots de passe ne correspondent pas.';
                                    editConfErr.style.display = 'block';
                                    editConfirmPwd.classList.add('is-invalid');
                                }
                            })
                            .catch(() => editForm.submit());
                    })
                    .catch(() => editForm.submit());
            });

            editCurPwd.addEventListener('input', function() {
                editCurErr.style.display = 'none';
                editCurPwd.classList.remove('is-invalid');
            });
            editConfirmPwd.addEventListener('input', function() {
                editConfErr.style.display = 'none';
                editConfirmPwd.classList.remove('is-invalid');
            });
        }

        /* ── Pré-remplissage de la modale d'édition si userToEdit ── */
        <?php if ($userToEdit): ?>
            (function() {
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                document.getElementById('edit_user_id').value = '<?= (int)$userToEdit->getId() ?>';
                document.getElementById('editUserLabel').textContent =
                    '<?= htmlspecialchars($userToEdit->getNom() . ' ' . $userToEdit->getPrenom() . ' (@' . $userToEdit->getUserName() . ')', ENT_QUOTES) ?>';
                document.getElementById('edit_role_id').value = '<?= (int)$userToEdit->getRoleId() ?>';
                document.getElementById('edit_active').value = '<?= (int)$userToEdit->getActive() ?>';
                modal.show();
            })();
        <?php endif; ?>
    });
    </script>