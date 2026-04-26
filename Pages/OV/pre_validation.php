<?php
session_start();

// Protection : Si on essaie d'accéder à la page sans les données POST, on redirige
if (!isset($_POST['header_info']) || !isset($_POST['factures_list'])) {
    header('Location: page_precedente.php'); // L'URL de votre page principale
    exit;
}

// On décode le JSON reçu depuis le formulaire caché
$header_info = json_decode($_POST['header_info'], true);
$factures_list = json_decode($_POST['factures_list'], true);
$draft_id = isset($_POST['draft_id']) ? $_POST['draft_id'] : null; // Optionnel

$page_title = "Détails de l'Ordre de Virement (Pré-validation)";
include '../Includes/header.php';
?>

<style>
    /* CSS basé sur votre design existant */
    .app-container {
        display: flex;
        min-height: 100vh;
        background-color: #0b0e11;
    }

    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .content-body {
        padding: 20px;
        flex: 1;
    }

    .page-main-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.6rem;
        margin: 0;
    }

    .card-custom {
        background-color: #15191d;
        border: 1px solid #24292d;
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .card-custom-header {
        padding: 15px 20px;
        background-color: #1a1e23;
        border-bottom: 1px solid #24292d;
    }

    .card-custom-header h5 {
        margin: 0;
        color: #00c3ff;
        font-size: 1rem;
        font-weight: 600;
    }

    .card-custom-body {
        padding: 20px;
    }

    /* Section des informations en haut (Style Grille pointillée de votre photo) */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 20px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px dashed #2d3239;
        padding: 10px 0;
    }

    .info-label {
        color: #a1a5b7;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .info-value {
        color: #ffffff;
        font-size: 0.9rem;
        font-weight: 500;
        text-align: right;
    }

    .info-value.highlight {
        color: #00c875;
        font-weight: 700;
        font-size: 1rem;
    }

    /* Bloc Numéro KTP au centre */
    .action-center-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 30px;
        background: rgba(0, 195, 255, 0.03);
        border-radius: 8px;
        border: 1px solid #24292d;
        margin-top: 10px;
    }

    .xtp-input-group {
        display: flex;
        width: 100%;
        max-width: 400px;
        margin-bottom: 15px;
    }

    .xtp-icon {
        background: #2d3239;
        color: #a1a5b7;
        padding: 12px 15px;
        border-radius: 8px 0 0 8px;
        border: 1px solid #495057;
        border-right: none;
    }

    .xtp-input {
        flex: 1;
        background-color: #1a1e23;
        border: 1px solid #495057;
        color: #fff;
        padding: 12px;
        border-radius: 0 8px 8px 0;
        outline: none;
        transition: 0.3s;
    }

    .xtp-input:focus {
        border-color: #00c875;
    }

    /* Bouton Vert */
    .btn-confirm-ov {
        background: linear-gradient(135deg, #00c875, #00a35e);
        color: white;
        border: none;
        padding: 14px;
        width: 100%;
        max-width: 400px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 200, 117, 0.2);
    }

    .btn-confirm-ov:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 200, 117, 0.3);
    }

    /* Tableau */
    .table-dark-modern thead th {
        background-color: #1a1e23;
        color: #5d666d;
        text-transform: uppercase;
        font-size: 0.75rem;
        border: none;
        padding: 15px;
    }

    .table-dark-modern tbody td {
        background-color: transparent;
        color: #e1e1e1;
        border-bottom: 1px solid #24292d;
        padding: 15px;
        vertical-align: middle;
        font-size: 0.85rem;
    }

    .badge-danger-custom {
        background-color: rgba(220, 53, 69, 0.15);
        color: #ff4d4d;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid rgba(220, 53, 69, 0.3);
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>

<?php include '../Includes/sidebar.php'; ?>
<main class="main-content">
    <?php include '../Includes/topbar.php'; ?>

    <div class="content-body">
        <div class="container-fluid">
            <h2 class="page-main-title mb-4"><?= $page_title ?></h2>

            <!-- BLOC 1 : Informations Clés & Confirmation KTP -->
            <div class="card-custom">
                <div class="card-custom-header">
                    <h5><i class="fas fa-info-circle me-2"></i> Informations Clés</h5>
                </div>
                <div class="card-custom-body">

                    <div class="info-grid">
                        <!-- Colonne Gauche -->
                        <div>
                            <div class="info-row"><span class="info-label">Structure:</span><span
                                    class="info-value"><?= htmlspecialchars($header_info['structure'] ?? '') ?></span>

                            </div>
                            <div class="info-row"><span class="info-label">Fournisseur:</span><span
                                    class="info-value"><?= htmlspecialchars($header_info['fournisseur'] ?? '') ?></span>
                            </div>
                            <div class="info-row"><span class="info-label">Contrat N°:</span><span
                                    class="info-value"><?= htmlspecialchars($header_info['contrat'] ?? '') ?></span>
                            </div>
                        </div>
                        <!-- Colonne Droite -->
                        <div>
                            <div class="info-row"><span class="info-label">Monnaie:</span><span
                                    class="info-value"><?= htmlspecialchars($header_info['devise'] ?? '') ?></span>
                            </div>

                            <!-- ICI : On remplace EXPLOITATION par la Nature envoyée depuis la première page -->
                            <div class="info-row"><span class="info-label">Nature:</span><span
                                    class="info-value"><?= htmlspecialchars($header_info['nature_ov'] ?? '') ?></span>
                            </div>

                            <div class="info-row"><span class="info-label">Montant Total:</span><span
                                    class="info-value highlight"><?= htmlspecialchars($header_info['montant_total'] ?? '') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Zone centrale (Champ KTP) -->
                    <div class="action-center-block">
                        <label style="color: #a1a5b7; font-weight: 600; font-size: 0.85rem; margin-bottom: 10px;">Numéro
                            KTP <span class="text-danger">*</span></label>
                        <form id="finalSubmitForm" action="#" method="POST" class="w-100 d-flex flex-column align-items-center">

                            <!-- Input KTP -->
                            <div class="xtp-input-group">
                                <span class="xtp-icon"><i class="fas fa-key"></i></span>
                                <input type="text" class="xtp-input" name="num_ktp" placeholder="Saisir le N° KTP..."
                                    required>
                            </div>

                            <!-- On conserve les données pour l'envoi final -->
                            <input type="hidden" name="header_info"
                                value='<?= htmlspecialchars(json_encode($header_info, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                            <input type="hidden" name="factures_list"
                                value='<?= htmlspecialchars(json_encode($factures_list, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                            <?php if ($draft_id): ?>
                                <input type="hidden" name="draft_id" value="<?= htmlspecialchars($draft_id) ?>">
                            <?php endif; ?>

                            <!-- Bouton Vert -->
                            <button type="submit" class="btn-confirm-ov">
                                <i class="fas fa-check-circle me-2"></i> Confirmer et Générer l'OV
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- BLOC 2 : Détail des Factures (DataTable) -->
            <div class="card-custom">
                <div class="card-custom-header">
                    <h5><i class="fas fa-list me-2"></i> Détail des Factures Incluses</h5>
                </div>
                <div class="card-custom-body">
                    <div class="table-responsive">
                        <table class="table table-dark-modern w-100" id="selectedFacturesTable">
                            <thead>
                                <tr>
                                    <th>N° Facture</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Devise</th>
                                    <th>Structure</th>
                                </tr>
                            </thead>
                            <tbody id="virementTbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php include '../Includes/footer.php'; ?>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<script>
    // 1. Bouton "Confirmer et Générer l'OV"
    document.getElementById('finalSubmitForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        // Récupération des données déjà présentes dans la page
        const header = <?= json_encode($header_info) ?>;
        const factures = <?= json_encode($factures_list) ?>;
        const ktp = document.querySelector('input[name="num_ktp"]').value;

        const btn = document.querySelector('.btn-confirm-ov');
        btn.disabled = true;
        btn.innerHTML = 'Traitement...';

        // Envoi des données à l'API via la fonction sendVirementData
        const response = await sendVirementData(header, factures, ktp);

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: response.message,
                background: '#15191d',
                color: '#fff',
                timer: 1500, // Attendre 1.5s pour que l'utilisateur lise le message
                showConfirmButton: false
            }).then(() => {
                
                // 🚀 C'EST ICI QUE SE FAIT LA MAGIE (Redirection 100% POST)
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'OV_detailles.php'; // On envoie vers la page détails

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ov_id'; 
                input.value = response.ov_id; // On met l'ID renvoyé par l'API

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit(); // Exécute le formulaire et change de page !
                
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: response.message,
                background: '#15191d',
                color: '#fff'
            });
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Confirmer et Générer l\'OV';
        }
    });

    // 2. Chargement du tableau des factures
    document.addEventListener('DOMContentLoaded', function() {
        const factures = <?php echo json_encode($factures_list); ?>;
        const tbody = document.getElementById('virementTbody');

        if (factures && factures.length > 0) {
            tbody.innerHTML = '';
            factures.forEach(f => {
                let tr = document.createElement('tr');
                tr.innerHTML = `
                <td><strong>${f.num_facture || ''}</strong></td>
                <td>${f.date || ''}</td>
                <td>${f.montant || ''}</td>
                <td>${f.devise || ''}</td>
                <td>${f.structure || ''}</td>
            `;
                tbody.appendChild(tr);
            });
        }

        if ($.fn.DataTable.isDataTable('#selectedFacturesTable')) {
            $('#selectedFacturesTable').DataTable().destroy();
        }

        $('#selectedFacturesTable').DataTable({
            language: {
                "sSearch": "Recherche:",
                "sEmptyTable": "Aucune facture sélectionnée"
            },
            dom: 'frtip',
            pageLength: 5,
            ordering: false
        });
    });

    // 3. Fonction d'envoi API (Allégée et propre)
    async function sendVirementData(header, factures, ktp) {
        const formData = new FormData();

        formData.append('header_info', JSON.stringify(header));
        formData.append('factures_list', JSON.stringify(factures));
        formData.append('num_ktp', ktp);

        try {
            const response = await fetch('../../Controllers/OV/ControllertOV.php', { 
                method: 'POST',
                body: formData
            });

            return await response.json();
            
        } catch (error) {
            console.error("Erreur API:", error);
            return {
                success: false,
                message: "Erreur lors de l'envoi au serveur."
            };
        }
    }
</script>