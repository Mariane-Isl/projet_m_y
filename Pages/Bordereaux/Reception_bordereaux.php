<?php
session_start();
unset($_SESSION['receptionner_bordereau_id'], $_SESSION['accuser_bordereau_id']);

require_once '../../classes/Database.php';
require_once '../../classes/Bordereau.php';

$database   = new Database();
$db         = $database->getConnection();
$bordereaux = Bordereau::getAllWithDetails_new($db);


$page_title = "Réception Bordereaux";
?>
<?php include '../Includes/header.php'; ?>
<?php include '../Includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="content-area">


        <?php if (isset($_SESSION['message'])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'error'; ?>',
                title: '<?php echo $_SESSION['message_type'] === 'success' ? 'Succès' : 'Erreur'; ?>',
                text: '<?php echo addslashes($_SESSION['message']); ?>',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        });
        </script>
        <?php
            // Nettoyer les messages après affichage
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <!-- Tableau des bordereaux -->
        <div class="section-card mt-3">
            <div class="section-header">
                <h3 class="section-title">Réception des Bordereaux</h3>
            </div>

            <div class="table-responsive" style="padding-bottom: 150px; min-height: 500px;">
                <table class="factures-table" id="tableBordereaux">
                    <thead>
                        <tr>
                            <th>N° Bordereau</th>
                            <th>Date Bordereau</th>
                            <th>N° Contrat</th>
                            <th>Fournisseur</th>
                            <th>Structure</th>
                            <th>Généré Par</th>
                            <th>Statut</th>
                            <th>Date Statut</th>
                            <th style="text-align:center;">Engagement</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bordereaux as $brd): ?>
                        <?php
                            $statutCode  = $brd['statut_code']  ?? 'TRANSMIS';
                            $statutLabel = $brd['statut_label'] ?? 'Transmis';

                            $badgeClass = match ($statutCode) {
                                'RECEPTION'    => 'badge-approved',
                                'NON_CONTROLE' => 'badge-pending',
                                'ARRIVE'       => 'badge-pending',
                                'TRANSMIS'     => 'badge-process',
                                default        => 'badge-process'
                            };

                            $estAccuse      = in_array($statutCode, ['NON_CONTROLE', 'ARRIVE', 'RECEPTION']);
                            $estReceptionne = ($statutCode === 'RECEPTION');
                            $emetteur       = trim(($brd['emetteur_nom'] ?? '') . ' ' . ($brd['emetteur_prenom'] ?? ''));
                            ?>
                        <tr>
                            <!-- Colonne 1 -->
                            <td><span
                                    class="badge-status badge-process"><?= htmlspecialchars($brd['num_bordereau']) ?></span>
                            </td>
                            <!-- Colonne 2 -->
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($brd['date_bordereau']))) ?></td>
                            <!-- Colonne 3 -->
                            <td style="font-size:0.82rem;"><?= htmlspecialchars($brd['num_Contrat'] ?? '—') ?></td>
                            <!-- Colonne 4 -->
                            <td style="font-size:0.83rem;">
                                <?= htmlspecialchars(($brd['fournisseur_code'] ?? '') . ' - ' . ($brd['nom_Fournisseur'] ?? '—')) ?>
                            </td>
                            <!-- Colonne 5 -->
                            <td style="color:var(--text-muted);font-size:0.82rem;">SIEGE-DP</td>
                            <!-- Colonne 6 -->
                            <td style="font-size:0.82rem;"><?= $emetteur ? htmlspecialchars($emetteur) : '—' ?></td>
                            <!-- Colonne 7 -->
                            <td><span
                                    class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($statutLabel) ?></span>
                            </td>
                            <!-- Colonne 8 -->
                            <td style="font-size:0.81rem;">
                                <?= !empty($brd['date_statut']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($brd['date_statut']))) : '—' ?>
                            </td>

                            <!-- Colonne 9 : Engagement -->
                            <td style="text-align:center;">
                                <button type="button" onclick="ouvrirModalContrat(<?= $brd['Contrat_id'] ?>)"
                                    class="btn-outline-blue"
                                    style="font-size:0.73rem;padding:4px 12px;border-radius:20px;">
                                    Contrat
                                </button>
                            </td>

                            <!-- Colonne 10 : Actions -->
                            <td style="text-align:center;">
                                <div class="brd-dropdown" id="dd-<?= $brd['id'] ?>">
                                    <button class="brd-btn-actions" onclick="toggleDD(<?= $brd['id'] ?>)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
                                        </svg>
                                        Actions
                                    </button>
                                    <div class="brd-dd-menu">

                                        <!-- ① Accusé de Réception -->
                                        <?php if ($estAccuse): ?>
                                        <span class="brd-dd-item brd-dd-done">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                            </svg>
                                            Accusé de Réception
                                        </span>
                                        <?php else: ?>
                                        <a href="#" class="brd-dd-item"
                                            onclick="event.preventDefault(); document.getElementById('form-accuse-<?= $brd['id'] ?>').submit();">
                                            <form id="form-accuse-<?= $brd['id'] ?>"
                                                action="../../Controllers/local_API/Bordereaux/accuser_bordereau.php"
                                                method="POST" style="display:none;">
                                                <input type="hidden" name="bordereau_id" value="<?= $brd['id'] ?>">
                                            </form>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                            </svg>
                                            Accusé de Réception
                                        </a>
                                        <?php endif; ?>

                                        <hr class="divider-section">

                                        <!-- ② Réception Bordereau -->
                                        <?php if ($estReceptionne): ?>
                                        <span class="brd-dd-item brd-dd-done">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                            </svg>
                                            Réception Bordereau
                                        </span>
                                        <?php elseif (!$estAccuse): ?>
                                        <span class="brd-dd-item brd-dd-disabled"
                                            title="Effectuez d'abord l'Accusé de Réception">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z" />
                                            </svg>
                                            Réception Bordereau
                                        </span>
                                        <?php else: ?>
                                        <a href="#" class="brd-dd-item"
                                            onclick="event.preventDefault(); document.getElementById('form-recept-<?= $brd['id'] ?>').submit();">
                                            <form id="form-recept-<?= $brd['id'] ?>" action="receptionner_bordereau.php"
                                                method="POST" style="display:none;">
                                                <input type="hidden" name="bordereau_id" value="<?= $brd['id'] ?>">
                                            </form>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M20 6h-2.18c.07-.31.18-.95C18 3.39 16.61 2 14.95 2c-.97 0-1.76.5-2.28 1.26L12 4l-.67-.74C10.81 2.5 10.02 2 9.05 2 7.39 2 6 3.39 6 5.05c0 .33.11.64.18.95H4c-1.1 0-2 .9-2 2v13c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z" />
                                            </svg>
                                            Réception Bordereau
                                        </a>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /content-area -->

    <!-- ════ MODALE : DÉTAILS CONTRAT ════ -->
    <div class="modal fade" id="modalContrat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color:var(--accent-blue);">
                        📄 Détails du Contrat
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <!-- Indicateur de chargement -->
                <div id="contratLoader" style="text-align:center;padding:40px;">
                    <div class="spinner-border text-info" role="status"></div>
                    <p style="margin-top:12px;color:var(--text-muted);font-size:0.83rem;">Chargement des données...</p>
                </div>

                <!-- Contenu chargé par AJAX -->
                <div id="contratContent" style="display:none;">
                    <div class="modal-body">
                        <div
                            style="background:rgba(59,158,255,0.05);border:1px solid rgba(59,158,255,0.1);border-radius:10px;padding:16px 20px;">
                            <p class="detail-card-title">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                                </svg>
                                Informations du Contrat
                            </p>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:0.85rem;">
                                <div>
                                    <span
                                        style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">N°
                                        Contrat</span>
                                    <div style="margin-top:4px;font-weight:700;">
                                        <span class="badge-status badge-process" id="detail_num_contrat">—</span>
                                    </div>
                                </div>
                                <div>
                                    <span
                                        style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">Fournisseur</span>
                                    <div style="margin-top:4px;font-weight:600;" id="detail_fournisseur">—</div>
                                </div>
                                <div>
                                    <span
                                        style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">Pays</span>
                                    <div style="margin-top:4px;font-weight:600;" id="detail_pays">—</div>
                                </div>
                                <div>
                                    <span
                                        style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">Gestionnaire</span>
                                    <div style="margin-top:4px;font-weight:600;" id="detail_gestionnaire">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="contratErreur" style="display:none;padding:20px;color:var(--accent-red);"></div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
        <?php include '../Includes/footer.php'; ?>
    </div>



    <script>
    /* ── DataTable ── */
    $(document).ready(function() {
        $('#tableBordereaux').DataTable({
            language: {
                search: 'Rechercher :',
                // On intègre votre HTML "empty-state" directement dans DataTables !
                emptyTable: `
<div class="empty-state" style="padding: 20px; text-align: center;">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width: 40px; height: 40px; color: #a0aec0; margin-bottom: 10px;">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z" />
                </svg>
                <p style="margin: 0; color: #718096; font-size: 0.9rem;">Aucun bordereau trouvé.</p>
            </div>
        `,
                zeroRecords: 'Aucun résultat correspondant à votre recherche'
            },
            paging: false,
            lengthChange: false,
            info: false,
            order: [],
            columnDefs: [{
                orderable: false
            }]
        });
    });

    /* ── Modale Détails Contrat (chargement AJAX) ── */
    function ouvrirModalContrat(contratId) {
        const loader = document.getElementById('contratLoader');
        const content = document.getElementById('contratContent');
        const erreur = document.getElementById('contratErreur');

        loader.style.display = 'block';
        content.style.display = 'none';
        erreur.style.display = 'none';

        new bootstrap.Modal(document.getElementById('modalContrat')).show();

        const fd = new FormData();
        fd.append('contrat_id', contratId);

        fetch('../../Controllers/LOCAL_API/Bordereaux/get_contrat_details_brd.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(function(data) {
                loader.style.display = 'none';
                if (data.success) {
                    const d = data.data;
                    document.getElementById('detail_num_contrat').textContent = d.num_contrat || '—';
                    document.getElementById('detail_fournisseur').textContent = d.nom_fournisseur || '—';
                    document.getElementById('detail_pays').textContent = d.pays_label || '—';
                    document.getElementById('detail_gestionnaire').textContent = d.gestionnaire || 'Non affecté';
                    content.style.display = 'block';
                } else {
                    erreur.style.display = 'block';
                    erreur.innerHTML = '❌ <strong>Erreur :</strong> ' + (data.message || 'Réponse invalide');
                }
            })
            .catch(function(e) {
                loader.style.display = 'none';
                erreur.style.display = 'block';
                erreur.innerHTML = '❌ <strong>Erreur réseau :</strong> ' + e.toString();
            });
    }
    </script>