<?php
// Détection de la page active
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

if (!function_exists('isActive')) {
    function isActive(...$pages)
    {
        global $currentPage, $currentDir;
        foreach ($pages as $p) {
            if ($currentPage === $p || $currentDir . '/' . $currentPage === $p) return 'active';
        }
        return '';
    }
}

// Chemin absolu URL vers la racine du projet
$projectRoot  = realpath(__DIR__ . '/../../');
$docRoot      = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');
$sidebarBase  = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));

// Fonction pour un lien absolu depuis la racine du projet
function linkTo($path)
{
    global $sidebarBase;
    return $sidebarBase . '/' . ltrim($path, '/');
}
?>
<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="sidebar">

    <!-- Marque / Logo -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-logo">
            <img src="<?= linkTo('dist/images/sonatrach.jpg') ?>" alt="Sonatrach">
        </div>
        <div class="sidebar-brand-text">
            <h2>SUIVI FACTURES</h2>
            <span>Direction des Projets</span>
        </div>
    </div>

    <!-- Utilisateur connecté -->
    <div class="sidebar-user">
        <div class="user-avatar">
            <?php
            $nom      = $_SESSION['nom'] ?? 'U';
            $initiale = strtoupper(mb_substr($nom, 0, 2));
            echo htmlspecialchars($initiale);
            ?>
        </div>
        <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['nom']) ?></strong>
            <?php
            $roleLabel = strtolower($_SESSION['role_label'] ?? 'user');
            $roleClass = 'role-' . str_replace([' ', '_'], '-', $roleLabel);
            ?>
            <div class="user-role <?= htmlspecialchars($roleClass) ?>">
                <?= htmlspecialchars($_SESSION['role_label']) ?>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <!-- ══ NAVIGATION MODERNISÉE ══ -->
    <nav class="sidebar-nav-modern">

        <!-- Section Label -->
        <div class="nav-section-label">MENU PRINCIPAL</div>

        <!-- 1. ACCUEIL -->
        <a class="nav-item-modern <?= isActive('dashboard.php') ?>" href="<?= linkTo('Pages/Dashboard/tableau_de_bord.php') ?>">
            <div class="nav-item-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="9" rx="1" />
                    <rect x="14" y="3" width="7" height="5" rx="1" />
                    <rect x="14" y="12" width="7" height="9" rx="1" />
                    <rect x="3" y="16" width="7" height="5" rx="1" />
                </svg>
            </div>
            <span class="nav-item-text">Accueil</span>
            <div class="nav-item-indicator"></div>
        </a>

        <!-- Section Label -->
        <div class="nav-section-label">GESTION</div>

        <!-- 2. ADMINISTRATION -->
        <div class="nav-group">
            <div class="nav-parent-modern" data-bs-toggle="collapse" data-bs-target="#menuAdmin">
                <div class="nav-parent-left">
                    <div class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                    </div>
                    <span class="nav-item-text">Administration</span>
                </div>
                <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
            <div id="menuAdmin" class="collapse nav-submenu <?= in_array($page_title, ['Fournisseurs', 'Monnaies', 'Utilisateurs', 'Régions']) ? 'show' : '' ?>">
                <a class="nav-sub-item-modern <?= ($page_title == 'Fournisseurs') ? 'active' : '' ?>" href="../Fournisseur/ListeFournisseur.php">
                    <span class="sub-dot"></span>Fournisseurs
                </a>
                <a class="nav-sub-item-modern <?= ($page_title == 'Monnaies') ? 'active' : '' ?>" href="../monnaies/ListeMonnaies.php">
                    <span class="sub-dot"></span>Monnaies
                </a>
                <a class="nav-sub-item-modern <?= ($page_title == 'Utilisateurs') ? 'active' : '' ?>" href="../Utilisateur/Liste_User.php">
                    <span class="sub-dot"></span>Utilisateurs
                </a>
                <a class="nav-sub-item-modern <?= ($page_title == 'Régions') ? 'active' : '' ?>" href="../regions/Liste_regions.php">
                    <span class="sub-dot"></span>Régions
                </a>
            </div>
        </div>

        <!-- 3. BORDEREAUX -->
        <div class="nav-group">
            <div class="nav-parent-modern" data-bs-toggle="collapse" data-bs-target="#menuBrd">
                <div class="nav-parent-left">
                    <div class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                    <span class="nav-item-text">Bordereaux</span>
                </div>
                <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
            <div id="menuBrd" class="collapse nav-submenu <?= in_array($page_title, ['Réception Bordereaux', 'Chargement Bordereaux']) ? 'show' : '' ?>">
                <a class="nav-sub-item-modern <?= ($page_title == 'Réception Bordereaux') ? 'active' : '' ?>" href="../Bordereaux/Reception_bordereaux.php">
                    <span class="sub-dot"></span>Réception Bordereaux
                </a>
                <a class="nav-sub-item-modern <?= ($page_title == 'Chargement Bordereaux') ? 'active' : '' ?>" href="../Bordereaux/chargementBordereaux.php">
                    <span class="sub-dot"></span>Chargement Bordereaux
                </a>
            </div>
        </div>

        <!-- 4. ORDRES DE VIREMENT -->
        <div class="nav-group">
            <div class="nav-parent-modern" data-bs-toggle="collapse" data-bs-target="#menuOV">
                <div class="nav-parent-left">
                    <div class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M3 9h18" />
                            <path d="M9 21V9" />
                        </svg>
                    </div>
                    <span class="nav-item-text">Ordres de Virement</span>
                </div>
                <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
            <div id="menuOV" class="collapse nav-submenu <?= in_array($page_title, ['Liste des OV', 'Création OV']) ? 'show' : '' ?>">
                <a class="nav-sub-item-modern <?= ($page_title == 'Liste des OV') ? 'active' : '' ?>" href="../OV/Liste_Ordre_Virement.php">
                    <span class="sub-dot"></span>Liste des OV
                </a>
                <a class="nav-sub-item-modern <?= ($page_title == 'Création OV') ? 'active' : '' ?>" href="../OV/creationOV.php">
                    <span class="sub-dot"></span>Création Ordre de Virement
                </a>
            </div>
        </div>

        <!-- 5. REJETS -->
        <div class="nav-group">
            <div class="nav-parent-modern" data-bs-toggle="collapse" data-bs-target="#menuRejet">
                <div class="nav-parent-left">
                    <div class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="15" y1="9" x2="9" y2="15" />
                            <line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                    </div>
                    <span class="nav-item-text">Rejets</span>
                </div>
                <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
            <div id="menuRejet" class="collapse nav-submenu <?= ($page_title == 'Liste des Rejets') ? 'show' : '' ?>">
                <a class="nav-sub-item-modern <?= ($page_title == 'Liste des Rejets') ? 'active' : '' ?>" href="../Rejet/Liste_rejet.php">
                    <span class="sub-dot"></span>Liste des Rejets
                </a>
            </div>
        </div>

        <!-- 6. FACTURES -->
        <div class="nav-group">
            <div class="nav-parent-modern" data-bs-toggle="collapse" data-bs-target="#menuFacture">
                <div class="nav-parent-left">
                    <div class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                            <polyline points="10 9 9 9 8 9" />
                        </svg>
                    </div>
                    <span class="nav-item-text">Factures</span>
                </div>
                <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
            <div id="menuFacture" class="collapse nav-submenu <?= ($page_title == 'Recherche Factures') ? 'show' : '' ?>">
                <a class="nav-sub-item-modern <?= ($page_title == 'Recherche Factures') ? 'active' : '' ?>" href="../Facture/Recherche_Facture.php">
                    <span class="sub-dot"></span>Recherche Factures
                </a>
            </div>
        </div>

    </nav>




    <!-- Déconnexion -->
    <div class="sidebar-footer">
        <a href="<?= linkTo('Controllers/Auth/logoutController.php') ?>" class="btn-logout">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                <path
                    d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
            </svg>
            Déconnexion
        </a>
    </div>

</aside>

<style>
    /* ══════════════════════════════════════════════════════════ */
    /* ══ MODERN DARK SIDEBAR NAVIGATION STYLES ══ */
    /* ══════════════════════════════════════════════════════════ */

    /* Sidebar Layout - Flexbox for sticky footer */
    .sidebar {
        display: flex;
        flex-direction: column;
        height: 100vh;
    }

    .sidebar-nav-modern {
        padding: 0 12px;
        margin-top: 10px;
        flex: 1;
        overflow-y: auto;
    }

    /* Section Labels */
    .nav-section-label {
        font-size: 0.65rem;
        font-weight: 700;
        color: #565d6d;
        letter-spacing: 1.2px;
        padding: 16px 14px 8px;
        text-transform: uppercase;
    }

    /* ── Accueil Single Item ── */
    .nav-item-modern {
        display: flex;
        align-items: center;
        padding: 12px 14px;
        margin: 4px 0;
        background: transparent;
        color: #8b919e;
        border-radius: 10px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .nav-item-modern:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
    }

    .nav-item-modern.active {
        background: rgba(96, 165, 250, 0.1);
        color: #60a5fa;
    }

    .nav-item-modern.active .nav-item-indicator {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 60%;
        background: #60a5fa;
        border-radius: 0 3px 3px 0;
    }

    .nav-item-icon {
        width: 20px;
        height: 20px;
        margin-right: 12px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-item-icon svg {
        width: 18px;
        height: 18px;
    }

    .nav-item-text {
        flex: 1;
    }

    /* ── Nav Group Container ── */
    .nav-group {
        margin: 2px 0;
    }

    /* ── Parent Items (Clean Dark Style) ── */
    .nav-parent-modern {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        background: transparent;
        color: #8b919e;
        border-radius: 10px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
    }

    .nav-parent-modern:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
    }

    .nav-parent-left {
        display: flex;
        align-items: center;
    }

    .nav-chevron {
        width: 16px;
        height: 16px;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0.5;
    }

    .nav-parent-modern[aria-expanded="true"] .nav-chevron {
        transform: rotate(180deg);
        opacity: 1;
    }

    .nav-parent-modern[aria-expanded="true"] {
        background: rgba(96, 165, 250, 0.08);
        color: #60a5fa;
    }

    .nav-parent-modern[aria-expanded="true"] .nav-item-icon svg {
        stroke: #60a5fa;
    }

    /* ── Submenu Container ── */
    .nav-submenu {
        padding: 4px 0 4px 20px;
        margin-top: 2px;
        position: relative;
    }

    .nav-submenu::before {
        content: '';
        position: absolute;
        left: 26px;
        top: 4px;
        bottom: 4px;
        width: 1px;
        background: rgba(96, 165, 250, 0.2);
        border-radius: 1px;
    }

    /* ── Sub Items ── */
    .nav-sub-item-modern {
        display: flex;
        align-items: center;
        padding: 10px 14px 10px 24px;
        color: #6b7280;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 500;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 8px;
        margin: 2px 0;
        position: relative;
    }

    .nav-sub-item-modern:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.03);
    }

    .nav-sub-item-modern:hover .sub-dot {
        background: #ffffff;
        transform: scale(1.3);
    }

    .nav-sub-item-modern.active {
        color: #60a5fa;
        background: rgba(96, 165, 250, 0.08);
        font-weight: 600;
    }

    .nav-sub-item-modern.active .sub-dot {
        background: #60a5fa;
        box-shadow: 0 0 8px rgba(96, 165, 250, 0.5);
    }

    .sub-dot {
        width: 5px;
        height: 5px;
        background: #4b5563;
        border-radius: 50%;
        margin-right: 12px;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    /* ══ Smooth Collapse Animation ══ */
    .nav-submenu.collapsing {
        transition: height 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ══ Scrollbar for long navigation ══ */
    .sidebar-nav-modern::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-nav-modern::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-nav-modern::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    .sidebar-nav-modern::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    /* ══ Sidebar Footer - Fixed at Bottom ══ */
    .sidebar-footer {
        padding: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        margin-top: auto;
    }

    .sidebar-footer .btn-logout {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.25s ease;
        width: 100%;
        justify-content: center;
    }

    .sidebar-footer .btn-logout:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }

    .sidebar-footer .btn-logout svg {
        width: 18px;
        height: 18px;
    }
</style>

<!-- JavaScript to keep menu open when clicking sub-items -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Store the open menu state in sessionStorage
        const openMenus = JSON.parse(sessionStorage.getItem('openMenus') || '[]');

        // Restore open menus on page load
        openMenus.forEach(function(menuId) {
            const menu = document.getElementById(menuId);
            if (menu) {
                menu.classList.add('show');
                const parent = menu.previousElementSibling;
                if (parent) {
                    parent.setAttribute('aria-expanded', 'true');
                }
            }
        });

        // Listen for menu toggle events
        document.querySelectorAll('.nav-parent-modern').forEach(function(parent) {
            parent.addEventListener('click', function() {
                const targetId = this.getAttribute('data-bs-target').replace('#', '');

                setTimeout(function() {
                    const currentOpenMenus = [];
                    document.querySelectorAll('.nav-submenu.show').forEach(function(menu) {
                        currentOpenMenus.push(menu.id);
                    });
                    sessionStorage.setItem('openMenus', JSON.stringify(currentOpenMenus));
                }, 350);
            });
        });

        // Keep track of which menu a sub-item belongs to before navigation
        document.querySelectorAll('.nav-sub-item-modern').forEach(function(subItem) {
            subItem.addEventListener('click', function() {
                const currentOpenMenus = [];
                document.querySelectorAll('.nav-submenu.show').forEach(function(menu) {
                    currentOpenMenus.push(menu.id);
                });
                sessionStorage.setItem('openMenus', JSON.stringify(currentOpenMenus));
            });
        });
    });
</script>