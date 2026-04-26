<?php
session_start();

// Si le brouillon n'existe pas en session, on renvoie à la création
if (!isset($_SESSION['rejet_draft']) || empty($_SESSION['rejet_draft']['factures_detail'])) {
    echo "<script>alert('Aucune facture trouvée ou sélectionnée. Veuillez recommencer.'); window.location.href='creation_rejet.php';</script>";
    exit;
}

$draft = $_SESSION['rejet_draft'];
$page_title = "Confirmation du Rejet";
?>

<!-- ================= DÉBUT DU HTML ================= -->
<?php include __DIR__ . '/../Includes/header.php'; ?>
<?php include __DIR__ . '/../Includes/sidebar.php'; ?>

<style>
    body { background-color: #0b0e11 !important; }
    .main-content { background-color: #0b0e11 !important; min-height: 100vh; color: #fff; }
    .card-dark { background-color: #15191d; border: 1px solid #24292d; border-radius: 10px; }
    .info-label { color: #5d666d; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.4px; }
    .info-value { color: #e8eaf0; font-size: 1.05rem; font-weight: 600; }
    .info-value.blue { color: #00c3ff; }
    textarea.motif-area { background: #0f1215 !important; border: 1px solid #24292d !important; color: #e8eaf0 !important; border-radius: 6px; font-size: 0.95rem; resize: vertical; width: 100%; padding: 12px 15px; outline: none; transition: border-color .2s; min-height: 120px; }
    textarea.motif-area:focus { border-color: #00c3ff !important; box-shadow: 0 0 0 2px rgba(0, 195, 255, .12); }
    .table-confirm td, .table-confirm th { background: transparent !important; border: none !important; border-bottom: 1px solid #1f2327 !important; padding: 15px 14px !important; color: #e8eaf0 !important; font-size: 0.95rem; vertical-align: middle; }
    .table-confirm thead th { color: #5d666d !important; font-size: 0.85rem !important; text-transform: uppercase; font-weight: 600; border-bottom: 2px solid #24292d !important; }
    .table-confirm tbody tr:hover { background: rgba(0, 195, 255, .03) !important; }
    .badge-facture { background: rgba(0, 195, 255, .1); color: #00c3ff; padding: 5px 13px; border-radius: 10px; font-size: 0.90rem; font-weight: 700; border: 1px solid rgba(0, 195, 255, .2); }
    .btn-confirm-create { background: #e74c3c; color: #fff; font-weight: 700; border: none; border-radius: 8px; padding: 12px 28px; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: opacity .2s; }
    .btn-confirm-create:hover { opacity: .85; }
    .btn-back { background: transparent; color: #8b949e; border: 1px solid #444c56; border-radius: 8px; padding: 12px 26px; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all .2s; text-decoration: none; }
    .btn-back:hover { border-color: #8b949e; color: #fff; }
    .btn-remove-row { background: rgba(231, 76, 60, .12); border: none; color: #e74c3c; cursor: pointer; padding: 6px 10px; border-radius: 5px; transition: background .2s; line-height: 1; }
    .btn-remove-row:hover { background: rgba(231, 76, 60, .25); }
    .section-title { font-size: 1.05rem; font-weight: 700; color: #e8eaf0; display: flex; align-items: center; gap: 8px; }
    .bread { font-size: 0.85rem; color: #5d666d; }
    .bread a { color: #5d666d; text-decoration: none; }
    .bread a:hover { color: #fff; }
    .required-star { color: #e74c3c; }
</style>

<div class="main-content">
    <?php include __DIR__ . '/../Includes/topbar.php'; ?>

    <div class="container-fluid p-4" style="max-width: 1200px;">

        <!-- GESTION DES MESSAGES D'ERREUR (Très important pour voir si ça plante) -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] === 'danger' ? 'danger' : 'success' ?> mb-4" style="background-color: #15191d; border-color: #e74c3c; color: #e74c3c;">
                <strong>Erreur détectée :</strong> <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="font-weight:700;margin:0;font-size:1.3rem;">Confirmation du rejet</h4>
            <div class="bread">
                <a href="../../Pages/dashboard.php">Accueil</a> <span style="margin:0 5px;">/</span>
                <a href="../../Controllers/Rejet/RejetController.php">Liste des Rejets</a>
                <span style="margin:0 5px;">/</span>
                <a href="creation_rejet.php">Création</a>
                <span style="margin:0 5px;">/</span>
                <span style="color:#fff;">Confirmation</span>
            </div>
        </div>

        <form method="POST" action="../../Controllers/Rejet/RejetController.php" id="formFinalCreate">
            <input type="hidden" name="action" value="create_rejet">

            <!-- ══ INFORMATIONS DU REJET ══ -->
            <div class="card-dark p-4 mb-4">
                <div class="section-title mb-4" style="padding-bottom:15px;border-bottom:1px solid #1f2327;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#00c3ff">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                    </svg>
                    Informations du Rejet
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:25px;">
                    <div>
                        <div class="info-label mb-1">Fournisseur</div>
                        <div class="info-value"><?= htmlspecialchars($draft['Nom_Fournisseur'] ?? '') ?></div>
                    </div>
                    <div>
                        <div class="info-label mb-1">Contrat</div>
                        <div class="info-value blue"><?= htmlspecialchars($draft['num_Contrat'] ?? '') ?></div>
                    </div>
                    <div>
                        <div class="info-label mb-1">Structure</div>
                        <div class="info-value"><?= htmlspecialchars($draft['structure_label'] ?? '') ?></div>
                    </div>
                </div>

                <div>
                    <label style="font-size:0.95rem;font-weight:600;color:#e8eaf0;margin-bottom:8px;display:block;">
                        Note / Motif du rejet <span class="required-star">*</span>
                    </label>
                    <textarea name="cause" class="motif-area" placeholder="Veuillez indiquer le motif détaillé du rejet..." required></textarea>
                </div>
            </div>

            <!-- ══ FACTURES À REJETER ══ -->
            <div class="card-dark mb-4">
                <div style="padding:18px 25px;border-bottom:1px solid #1f2327;">
                    <div class="section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#00c3ff">
                            <path d="M3 9h2V7H3v2zm0 4h2v-2H3v2zm0 4h2v-2H3v2zm4-8h11V7H7v2zm0 4h11v-2H7v2zm0 4h11v-2H7v2z" />
                        </svg>
                        Factures à rejeter
                        <span id="badge-count" style="background:#24292d;color:#8b949e;font-size:.85rem;padding:3px 10px;border-radius:12px;margin-left:8px;">
                            <?= count($draft['factures_detail']) ?>
                        </span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-confirm mb-0" id="tableConfirm">
                        <thead>
                            <tr>
                                <th>ID Facture</th>
                                <th>N° Facture</th>
                                <th>Date</th>
                                <th style="text-align:right;">Montant</th>
                                <th>Monnaie</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="facturesBody">
                            <?php foreach ($draft['factures_detail'] as $f): ?>
                                <tr id="row-<?= $f['id'] ?>">
                                    <td style="color:#5d666d; font-weight: 600;"><?= $f['id'] ?></td>
                                    <td><span class="badge-facture"><?= htmlspecialchars($f['Num_facture'] ?? '') ?></span></td>
                                    <td style="color:#8b949e;">
                                        <?= !empty($f['date_facture']) ? date('d/m/Y', strtotime($f['date_facture'])) : '—' ?>
                                    </td>
                                    <td style="text-align:right;font-weight:700;">
                                        <?= number_format(floatval($f['Montant'] ?? 0), 2, '.', ' ') ?>
                                    </td>
                                    <td style="color:#8b949e; font-weight: 600;"><?= htmlspecialchars($f['monnaie_code'] ?? '') ?></td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-remove-row" onclick="retirerFacture(<?= $f['id'] ?>)" title="Retirer cette facture">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══ ACTION BUTTONS ══ -->
            <div style="display:flex;justify-content:flex-end;gap:15px;align-items:center;">
                <button type="button" class="btn-back" onclick="annulerEtRetourner()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" /></svg>
                    Annuler et Retourner
                </button>
                <button type="button" class="btn-confirm-create" onclick="confirmerCreation()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" /></svg>
                    Confirmer la création du rejet
                </button>
            </div>
        </form>
        <?php include __DIR__ . '/../Includes/footer.php'; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let remainingIds = <?= json_encode(array_column($draft['factures_detail'], 'id')) ?>;

    function retirerFacture(id) {
        const row = document.getElementById('row-' + id);
        if (row) {
            row.style.transition = 'opacity .2s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 200);
        }
        remainingIds = remainingIds.filter(i => i !== id);
        const badge = document.getElementById('badge-count');
        if (badge) badge.textContent = remainingIds.length;
    }

    function annulerEtRetourner() { window.location.href = 'creation_rejet.php'; }

    function confirmerCreation() {
        if (remainingIds.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Aucune facture', text: 'Vous avez retiré toutes les factures. Veuillez en garder au moins une.', confirmButtonColor: '#00c3ff' });
            return;
        }

        const cause = document.querySelector('textarea[name="cause"]').value.trim();
        if (!cause) {
            Swal.fire({ icon: 'warning', title: 'Motif requis', text: 'Veuillez saisir le motif du rejet avant de confirmer.', confirmButtonColor: '#00c3ff' });
            return;
        }

        document.querySelectorAll('input[name="facture_ids[]"]').forEach(e => e.remove());
        remainingIds.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'facture_ids[]'; inp.value = id;
            document.getElementById('formFinalCreate').appendChild(inp);
        });

        Swal.fire({
            title: 'Confirmer la création ?',
            html: `Le rejet sera créé avec <strong>${remainingIds.length} facture(s)</strong>.`,
            icon: 'question', showCancelButton: true, confirmButtonColor: '#e74c3c', cancelButtonColor: '#444c56',
            confirmButtonText: '✅ Confirmer la création', cancelButtonText: 'Annuler'
        }).then(r => {
            if (r.isConfirmed) {
                const btn = document.querySelector('.btn-confirm-create');
                btn.disabled = true;
                btn.innerHTML = 'Création en cours...';
                document.getElementById('formFinalCreate').submit();
            }
        });
    }
</script>