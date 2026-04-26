<!DOCTYPE html>
<html lang="fr">
<!--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  VUE PDF : Template HTML pour DomPDF                            ║
    ║  Chemin : Views/pdf/bordereau_pdf.php                           ║
    ╚══════════════════════════════════════════════════════════════════╝

    ⚠ RÈGLES D'OR pour un HTML compatible DomPDF :
    ─────────────────────────────────────────────
    1. CSS INLINE ou dans <style> uniquement — pas de fichier CSS externe
    2. PAS de Flexbox ni Grid (support très limité dans DomPDF)
       → Utiliser des <table> pour la mise en page
    3. PAS de Bootstrap, PAS de FontAwesome
    4. Images : chemin ABSOLU sur le serveur (file:// ou URL complète)
    5. Polices : utiliser les polices intégrées à DomPDF
       (DejaVu Sans, Times New Roman, Courier, Helvetica)
    6. Pas de JavaScript
    7. Les variables PHP ($bordereau, $factures, etc.) viennent du Contrôleur
       via ob_start() / include / ob_get_clean()
-->
<head>
    <meta charset="UTF-8">
    <title>Bordereau <?= htmlspecialchars($bordereau['num_bordereau']) ?></title>

    <style>
        /* ─────────────────────────────────────────
           STYLES GLOBAUX
        ───────────────────────────────────────── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1a1a2e;
            background: #fff;
            line-height: 1.5;
        }

        /* ─────────────────────────────────────────
           HEADER DU DOCUMENT (logo + titre société)
        ───────────────────────────────────────── */
        .doc-header {
            width: 100%;
            border-bottom: 3px solid #0057a8;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }
        .doc-header table {
            width: 100%;
            border-collapse: collapse;
        }
        .doc-header .logo-cell {
            width: 120px;
            vertical-align: middle;
            text-align: center;
        }
        .doc-header .logo-cell img {
            max-width: 100px;
            max-height: 65px;
        }
        .doc-header .logo-text-arabic {
            font-size: 13pt;
            color: #1a1a2e;
            direction: rtl;
            display: block;
        }
        .doc-header .logo-text-latin {
            font-size: 11pt;
            font-weight: bold;
            color: #e5720f;
            letter-spacing: 2px;
            display: block;
            margin-top: 2px;
        }
        .doc-header .logo-bar {
            width: 50px;
            height: 3px;
            background: #e5720f;
            margin: 5px auto 0;
        }
        .doc-header .title-cell {
            vertical-align: middle;
            text-align: right;
            padding-right: 5px;
        }
        .doc-header .doc-main-title {
            font-size: 14pt;
            font-weight: bold;
            color: #0057a8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-header .doc-sub-title {
            font-size: 9pt;
            color: #555;
            margin-top: 4px;
        }

        /* ─────────────────────────────────────────
           BLOC D'INFORMATIONS (métadonnées bordereau)
        ───────────────────────────────────────── */
        .info-block {
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
        .info-block-title {
            background: #0057a8;
            color: #fff;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px 10px;
        }
        .info-block table {
            width: 100%;
            border-collapse: collapse;
            padding: 8px 10px;
        }
        .info-block table td {
            padding: 4px 10px;
            font-size: 9.5pt;
            vertical-align: top;
        }
        .info-block .lbl {
            font-weight: bold;
            color: #374151;
            width: 160px;
            white-space: nowrap;
        }
        .info-block .val {
            color: #1a1a2e;
        }
        .info-block .val.mono {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #0057a8;
        }

        /* Badge statut dans l'info block */
        .badge-statut {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8.5pt;
            font-weight: bold;
        }
        .badge-arrive       { background: #fef3c7; color: #92400e; }
        .badge-non-controle { background: #dbeafe; color: #1e40af; }
        .badge-success      { background: #d1fae5; color: #065f46; }
        .badge-default      { background: #f3f4f6; color: #374151; }

        /* ─────────────────────────────────────────
           TITRE CENTRAL DU DOCUMENT
        ───────────────────────────────────────── */
        .doc-central-title {
            text-align: center;
            margin-bottom: 16px;
        }
        .doc-central-title h2 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a1a2e;
            letter-spacing: 1.5px;
            border-bottom: 2px solid #0057a8;
            display: inline-block;
            padding-bottom: 4px;
        }
        .doc-central-title p {
            font-size: 8.5pt;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ─────────────────────────────────────────
           TABLEAU DES FACTURES
        ───────────────────────────────────────── */
        .factures-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        .factures-table thead tr {
            background: #1a2332;
            color: #fff;
        }
        .factures-table th {
            padding: 7px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #1a2332;
        }
        .factures-table td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            vertical-align: middle;
        }
        /* Alternance de couleurs */
        .factures-table tbody tr.row-pair {
            background: #f9fafb;
        }
        .factures-table tbody tr.row-impair {
            background: #ffffff;
        }
        /* Surbrillance facture trouvée */
        .factures-table tbody tr.row-found {
            background: #f0fdf4;
        }
        /* Surbrillance facture non trouvée */
        .factures-table tbody tr.row-not-found {
            background: #fff7f7;
        }

        /* Badge statut facture */
        .fac-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .fac-found     { background: #d1fae5; color: #065f46; }
        .fac-not-found { background: #fee2e2; color: #991b1b; }
        .fac-waiting   { background: #f3f4f6; color: #6b7280; }

        /* Montant (aligné à droite) */
        .text-right { text-align: right; }
        .text-center{ text-align: center; }
        .font-mono  { font-family: 'Courier New', monospace; }
        .font-bold  { font-weight: bold; }
        .color-blue { color: #0057a8; }

        /* ─────────────────────────────────────────
           LIGNE TOTAUX (pied de tableau)
        ───────────────────────────────────────── */
        .totaux-row td {
            background: #1a2332 !important;
            color: #fff !important;
            font-weight: bold;
            font-size: 9pt;
            padding: 7px 8px;
            border: 1px solid #0057a8;
        }

        /* ─────────────────────────────────────────
           SECTION SIGNATURES
        ───────────────────────────────────────── */
        .signatures-section {
            width: 100%;
            margin-top: 25px;
            border-top: 1px solid #d1d5db;
            padding-top: 15px;
        }
        .signatures-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures-section td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
            font-size: 9.5pt;
        }
        .sig-label {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        .sig-line {
            border-bottom: 1px solid #555;
            margin-top: 35px;
            margin-bottom: 5px;
        }
        .sig-name {
            font-size: 8.5pt;
            color: #6b7280;
            text-align: center;
        }

        /* ─────────────────────────────────────────
           FOOTER DU PDF (numérotation de page)
           DomPDF supporte les compteurs de page via CSS @page
        ───────────────────────────────────────── */
        @page {
            margin: 1.5cm 1.5cm 2cm 1.5cm;

            /* Zone de pied de page dans la marge basse */
            @bottom-center {
                content: "Page " counter(page) " / " counter(pages);
                font-family: 'DejaVu Sans', sans-serif;
                font-size: 8pt;
                color: #6b7280;
            }

            @bottom-left {
                content: "Document généré le <?= date('d/m/Y à H:i') ?>";
                font-family: 'DejaVu Sans', sans-serif;
                font-size: 8pt;
                color: #9ca3af;
            }

            @bottom-right {
                content: "CONFIDENTIEL — Usage interne";
                font-family: 'DejaVu Sans', sans-serif;
                font-size: 8pt;
                color: #9ca3af;
            }
        }

        /* Éviter les coupures de page dans les lignes du tableau */
        .factures-table tbody tr {
            page-break-inside: avoid;
        }
        .info-block {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <!-- ══════════════════════════════════════════════════
         EN-TÊTE : Logo Sonatrach + titre document
    ════════════════════════════════════════════════════ -->
    <div class="doc-header">
        <table>
            <tr>
                <!-- Logo -->
                <td class="logo-cell">
                    <?php
                    // ⚠ DomPDF nécessite un chemin absolu pour les images.
                    // realpath() convertit le chemin relatif en absolu.
                    $logoPath = realpath(__DIR__ . '/../../assets/images/logo_sonatrach.png');
                    ?>
                    <?php if ($logoPath && file_exists($logoPath)): ?>
                        <img src="<?= $logoPath ?>" alt="Sonatrach">
                    <?php else: ?>
                        <!-- Fallback texte si logo absent -->
                        <span class="logo-text-arabic">سوناطراك</span>
                        <span class="logo-text-latin">sonatrach</span>
                        <div class="logo-bar"></div>
                    <?php endif; ?>
                </td>
                <!-- Titre principal -->
                <td class="title-cell">
                    <div class="doc-main-title">PGSF — Plateforme de Suivi des Factures</div>
                    <div class="doc-sub-title">Direction des Programmes — Sonatrach</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════
         BLOC D'INFORMATIONS DU BORDEREAU
    ════════════════════════════════════════════════════ -->
    <div class="info-block">
        <div class="info-block-title">Informations du Bordereau</div>
        <table>
            <tr>
                <td class="lbl">N° Bordereau :</td>
                <td class="val mono"><?= htmlspecialchars($bordereau['num_bordereau']) ?></td>
                <td class="lbl">Date Bordereau :</td>
                <td class="val"><?= !empty($bordereau['date_bordereau']) ? date('d/m/Y', strtotime($bordereau['date_bordereau'])) : '—' ?></td>
            </tr>
            <tr>
                <td class="lbl">Contrat N° :</td>
                <td class="val mono"><?= htmlspecialchars($bordereau['num_contrat'] ?? '—') ?></td>
                <td class="lbl">Fournisseur :</td>
                <td class="val font-bold"><?= htmlspecialchars($bordereau['nom_Fournisseur'] ?? '—') ?></td>
            </tr>
            <tr>
                <td class="lbl">Émetteur :</td>
                <td class="val"><?= htmlspecialchars(trim(($bordereau['emetteur_nom'] ?? '') . ' ' . ($bordereau['emetteur_prenom'] ?? ''))) ?></td>
                <td class="lbl">Structure :</td>
                <td class="val"><?= htmlspecialchars($bordereau['region_label'] ?? '—') ?></td>
            </tr>
            <tr>
                <td class="lbl">Destinataire :</td>
                <td class="val" colspan="3">SH/DP/SIEGE — Direction Finance &amp; Comptabilité / Service Trésorerie Devises</td>
            </tr>
            <tr>
                <td class="lbl">Statut actuel :</td>
                <td class="val" colspan="3">
                    <?php
                    $sc = $bordereau['statut_code'] ?? '';
                    $badgeCls = match($sc) {
                        'ARRIVE'       => 'badge-arrive',
                        'NON_CONTROLE' => 'badge-non-controle',
                        'CONTROLE',
                        'RECEPTION'    => 'badge-success',
                        default        => 'badge-default',
                    };
                    ?>
                    <span class="badge-statut <?= $badgeCls ?>">
                        <?= htmlspecialchars($bordereau['statut_label'] ?? 'Inconnu') ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════
         TITRE CENTRAL
    ════════════════════════════════════════════════════ -->
    <div class="doc-central-title">
        <h2>Bordereau d'Envoi à la Trésorerie</h2>
        <p>Récapitulatif des factures transmises et leur statut de réception</p>
    </div>

    <!-- ══════════════════════════════════════════════════
         TABLEAU DES FACTURES
    ════════════════════════════════════════════════════ -->
    <table class="factures-table">
        <thead>
            <tr>
                <th>#</th>
                <th>N° Facture</th>
                <th>Date Facture</th>
                <th class="text-right">Montant</th>
                <th class="text-center">Devise</th>
                <th>Statut</th>
                <th class="text-center">Date Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($factures)): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding:15px;color:#9ca3af;font-style:italic;">
                        Aucune facture attachée à ce bordereau.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($factures as $i => $fac): ?>
                    <?php
                    $statutCode  = $fac['statut_code']  ?? null;
                    $statutLabel = $fac['statut_label'] ?? null;
                    $estTrouvee  = !empty($statutCode) && $statutCode !== 'TRANSMIS';

                    // Classe de ligne
                    $rowClass  = $estTrouvee ? 'row-found' : 'row-not-found';

                    // Badge statut
                    if ($estTrouvee) {
                        $badgeCls  = 'fac-found';
                        $badgeTxt  = htmlspecialchars($statutLabel);
                    } else {
                        $badgeCls  = 'fac-not-found';
                        $badgeTxt  = 'Non trouvée';
                    }
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="text-center" style="color:#9ca3af;font-size:8pt;">
                            <?= $i + 1 ?>
                        </td>
                        <td class="font-mono font-bold color-blue">
                            <?= htmlspecialchars($fac['Num_facture'] ?? '—') ?>
                        </td>
                        <td>
                            <?= !empty($fac['date_facture'])
                                ? date('d/m/Y', strtotime($fac['date_facture']))
                                : '—' ?>
                        </td>
                        <td class="text-right font-mono">
                            <?= $fac['Montant'] !== null
                                ? number_format((float)$fac['Montant'], 2, ',', ' ')
                                : '—' ?>
                        </td>
                        <td class="text-center font-bold">
                            <?= htmlspecialchars($fac['devise'] ?? '—') ?>
                        </td>
                        <td>
                            <span class="fac-badge <?= $badgeCls ?>">
                                <?= $badgeTxt ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?= !empty($fac['date_statut'])
                                ? date('d/m/Y', strtotime($fac['date_statut']))
                                : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="totaux-row">
                <td colspan="5" class="text-right">Nombre total de factures :</td>
                <td colspan="2" class="text-center">
                    <?= $nb_total ?> total
                    — <?= $nb_trouves ?> trouvée(s)
                    — <?= $nb_non_trouves ?> non trouvée(s)
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- ══════════════════════════════════════════════════
         SECTION SIGNATURES
    ════════════════════════════════════════════════════ -->
    <div class="signatures-section">
        <table>
            <tr>
                <td>
                    <div class="sig-label">Cachet et Signature de l'Émetteur</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">
                        <?= htmlspecialchars(trim(($bordereau['emetteur_nom'] ?? '') . ' ' . ($bordereau['emetteur_prenom'] ?? ''))) ?>
                    </div>
                </td>
                <td>
                    <div class="sig-label">Cachet et Signature du Réceptionnaire</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">Reçu le : _________________________</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
