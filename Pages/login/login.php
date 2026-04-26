<?php session_start(); ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Suivi Factures DP</title>
    <?php
    // login.php est dans Pages/login/ → ../../ = racine projet
    $projectRoot = realpath(__DIR__ . '/../../');
    $docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');
    $baseUrl     = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
    $cssBase     = $baseUrl . '/dist/css/';
    $jsBase      = $baseUrl . '/dist/js/';
    $imgBase     = $baseUrl . '/dist/images/';
    ?>
    <link rel="stylesheet" href="<?= $cssBase ?>bootstrap.min.css">
    <link rel="stylesheet" href="<?= $cssBase ?>login.css">
</head>
<body>
    <div class="bg-blob blob1"></div>
    <div class="bg-blob blob2"></div>
    <div class="bg-blob blob3"></div>

    <div class="login-wrapper">
        <div class="glass-card">

            <!-- Marque -->
            <div class="brand">
                <div class="brand-icon">
                    <img src="<?= $imgBase ?>sonatrach.jpg" alt="Logo Sonatrach">
                </div>
                <h1>SUIVI FACTURES</h1>
                <p>Division de Production</p>
            </div>

            <!-- Message d'erreur -->
            <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger mb-3">
                <?= htmlspecialchars($_SESSION['login_error']) ?>
            </div>
            <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <?php
            $loginAction = $baseUrl . '/Controllers/Auth/LoginController.php';
            ?>
            <form action="<?= $loginAction ?>" method="POST" novalidate>

                <div class="mb-3">
                    <label class="form-label">Nom d'utilisateur</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.7 0 4-1.8 4-4s-1.3-4-4-4-4 1.8-4 4 1.3 4 4 4zm0 2c-4 0-6 2-6 3v1h12v-1c0-1-2-3-6-3z"/>
                            </svg>
                        </span>
                        <input type="text" class="form-control" name="username"
                               placeholder="Entrez votre nom d'utilisateur"
                               required autocomplete="username">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 8h-1V6a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2zm-6 9a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm3.1-9H8.9V6a3.1 3.1 0 0 1 6.2 0v2z"/>
                            </svg>
                        </span>
                        <input type="password" class="form-control" name="password"
                               placeholder="••••••••"
                               required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-login">Se Connecter →</button>
            </form>

            <p class="footer-text">&copy; 2025–2026 Direction des Projets &middot; PFE ASMA</p>
        </div>
    </div>

    <script src="<?= $jsBase ?>bootstrap.bundle.min.js"></script>
</body>
</html>
