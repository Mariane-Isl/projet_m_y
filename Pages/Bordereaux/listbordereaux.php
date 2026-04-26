<?php
session_start();

require_once '../../classes/Database.php';
require_once '../../classes/Bordereau.php';

$database   = new Database();
$db         = $database->getConnection();
$bordereaux = Bordereau::getAllReceptionnes($db);

$page_title = "Bordereaux Réceptionnés";
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <div class="content-area">

        <!-- ── FLASH MESSAGE (SweetAlert comme Liste_regions) ── -->
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
                <h3 class="section-title">Bordereaux Réceptionnés</h3>

                <!-- Compteur + barre de recherche -->
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="position:relative;">
                        <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);opacity:.4;pointer-events:none;"
                             width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" id="searchInput" autocomplete="off"
                            placeholder="Rechercher..."
                            style="background:rgba(255,255,255,.05);border:1px solid var(--card-border);
                                   color:var(--text-main);border-radius:var(--radius-sm);
                                   padding:6px 12px 6px 32px;font-size:.8rem;width:220px;
                                   font-family:'DM Sans',sans-serif;">
                    </div>
                    <span class="badge-status badge-process"
                          style="font-size:.72rem;padding:4px 12px;">
                        <?= count($bordereaux) ?> bordereau(x)
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="factures-table" id="mainTable">
                    <thead>
                        <tr>
                            <th style="width:14%;">N° Bordereau</th>
                            <th style="width:9%;">Date</th>
                            <th style="width:10%;">Contrat</th>
                            <th style="width:14%;">Fournisseur</th>
                            <th>Structure Émettrice</th>
                            <th style="width:8%;text-align:center;">Nb Factures</th>
                            <th style="width:11%;">Statut</th>
                            <th style="width:8%;text-align:center;">Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bordereaux)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0
                                                 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <p>Aucun bordereau réceptionné pour le moment.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($bordereaux as $brd): ?>
                        <?php
                            $statutMap = [
                                'ARRIVE'       => 'badge-pending',
                                'NON_CONTROLE' => 'badge-process',
                                'CONTROLE'     => 'badge-approved',
                                'RECEPTION'    => 'badge-approved',
                            ];
                            $badgeClass = $statutMap[$brd['statut_code'] ?? ''] ?? 'badge-violet';
                        ?>
                        <tr>
                            <td>
                                <strong style="font-family:'Courier New',monospace;
                                               color:var(--accent-blue);font-size:.82rem;">
                                    <?= htmlspecialchars($brd['num_bordereau']) ?>
                                </strong>
                            </td>
                            <td style="color:var(--text-muted);font-size:.8rem;">
                                <?= $brd['date_bordereau']
                                    ? date('d/m/Y', strtotime($brd['date_bordereau']))
                                    : '—' ?>
                            </td>
                            <td>
                                <span class="badge-status badge-process"
                                      style="font-family:'Courier New',monospace;font-size:.72rem;">
                                    <?= htmlspecialchars($brd['num_contrat'] ?? '—') ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color:var(--text-main);">
                                    <?= htmlspecialchars($brd['nom_fournisseur'] ?? '—') ?>
                                </strong>
                            </td>
                            <td>
                                <?php if (!empty($brd['region_code'])): ?>
                                    <span class="badge-status badge-violet"
                                          style="font-size:.68rem;margin-right:5px;">
                                        <?= htmlspecialchars($brd['region_code']) ?>
                                    </span>
                                <?php endif; ?>
                                <span style="color:var(--text-secondary);font-size:.82rem;">
                                    <?= htmlspecialchars($brd['region_label'] ?? '—') ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                                             width:24px;height:24px;border-radius:50%;
                                             background:var(--accent-blue);color:#fff;
                                             font-size:.72rem;font-weight:700;">
                                    <?= (int)($brd['nb_factures'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-status <?= $badgeClass ?>">
                                    <?= htmlspecialchars($brd['statut_label'] ?? 'Inconnu') ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <form method="POST" action="detail_bordereau.php" style="display:inline;">
                                    <input type="hidden" name="bordereau_id" value="<?= (int)$brd['id'] ?>">
                                    <button type="submit" class="btn-outline-blue"
                                            style="padding:5px 12px;font-size:.76rem;border:none;cursor:pointer;
                                                   font-family:'DM Sans',sans-serif;"
                                            title="Voir les détails">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2.5">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        Détails
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div><!-- /content-area -->
</div><!-- /main-content -->


<script>
document.getElementById('searchInput').addEventListener('input', function() {
    const term = this.value.toLowerCase().trim();
    document.querySelectorAll('#mainTable tbody tr').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});
</script>