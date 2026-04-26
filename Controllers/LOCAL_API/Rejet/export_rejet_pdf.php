<?php
session_start();
require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/Rejet.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: ../../Controllers/Rejets/RejetController.php?page=liste");
    exit();
}

$db   = (new Database())->getConnection();
$data = Rejet::getDetailsById($db, $id);

if (!$data['infos']) {
    die("Rejet introuvable.");
}

$rejet    = $data['infos'];
$factures = $data['factures'];

// Codification
$regionCode   = strtoupper($rejet['region_code'] ?? 'XX');
$numPadded    = str_pad($rejet['num_rejet'] ?? '0', 3, '0', STR_PAD_LEFT);
$codification = "SH/DP/REJ/{$regionCode}/{$numPadded}";

// Date
$dateRejet = !empty($rejet['date_rejet'])
    ? (new DateTime($rejet['date_rejet']))->format('d/m/Y')
    : date('d/m/Y');

// Logo URL
$projectRoot = realpath(__DIR__ . '/../../../');
$docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');
$baseUrl     = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
$logoUrl     = $baseUrl . '/dist/images/sonatrach.jpg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>NOTE DE REJET — <?= htmlspecialchars($codification) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size:11px; color:#000; background:#fff; }

        .page { width:210mm; min-height:297mm; margin:0 auto; padding:12mm 15mm 20mm; background:#fff; position:relative; page-break-after:always; }
        .page:last-child { page-break-after:auto; }

        .doc-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:6mm; }
        .logo-block img { width:50px; height:auto; }
        .logo-block .company-lines { font-size:7px; color:#333; margin-top:3px; line-height:1.4; }
        .title-block { text-align:center; flex:1; padding:0 10mm; }
        .title-block h1 { font-size:14px; font-weight:bold; text-decoration:underline; letter-spacing:0.5px; margin-bottom:2mm; }
        .date-block { text-align:right; font-size:10px; white-space:nowrap; padding-top:4px; }

        .exp-dest-row { display:flex; gap:8mm; margin-bottom:5mm; }
        .box-label { border:1px solid #000; flex:1; }
        .box-label .box-title { background:#000; color:#fff; text-align:center; font-size:9px; font-weight:bold; padding:2px 4px; letter-spacing:0.5px; }
        .box-label .box-value { text-align:center; font-size:11px; font-weight:bold; padding:4px 6px 5px; min-height:22px; }

        .ref-line { font-size:10px; margin-bottom:5mm; }
        .ref-line strong { font-weight:bold; }

        .designation-box { border:1px solid #000; margin-bottom:6mm; }
        .designation-box .desig-title { background:#000; color:#fff; text-align:center; font-size:11px; font-weight:bold; padding:3px; letter-spacing:1px; }
        .designation-box .desig-body { padding:5mm; font-size:11px; line-height:1.7; }
        .designation-box .desig-body .intro-text { margin-bottom:4mm; }
        .designation-box .desig-body .motif-item { margin-left:6mm; margin-bottom:2mm; }
        .designation-box .desig-body .closing { margin-top:5mm; }

        .section-title { border:1px solid #000; text-align:center; font-weight:bold; font-size:11px; padding:3px; }
        .factures-table { width:100%; border-collapse:collapse; font-size:10px; }
        .factures-table th { border:1px solid #000; background:#f0f0f0; text-align:center; padding:3px 5px; font-weight:bold; }
        .factures-table td { border:1px solid #000; text-align:center; padding:3px 5px; }
        .factures-table td.montant { text-align:right; padding-right:8px; }

        .signature-area { margin-top:10mm; font-size:10px; }
        .signature-line { border-bottom:1px solid #000; width:60%; display:inline-block; }

        .doc-footer { position:absolute; bottom:8mm; left:15mm; right:15mm; border-top:1px solid #999; padding-top:2mm; font-size:7px; color:#555; text-align:center; line-height:1.5; }
        .page-num  { position:absolute; bottom:18mm; right:15mm; font-size:8px; color:#555; }

        .small-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:4mm; padding-bottom:2mm; border-bottom:1px solid #ccc; }
        .small-header img { width:30px; }
        .small-header .small-title { font-size:8px; color:#555; }
        .small-header .small-date  { font-size:8px; color:#555; }

        @media print {
            body { background:#fff !important; }
            .page { margin:0; padding:12mm 15mm 20mm; box-shadow:none; }
            .no-print { display:none !important; }
            @page { size:A4; margin:0; }
        }
        @media screen {
            body { background:#e0e0e0; padding:20px 0; }
            .page { box-shadow:0 2px 12px rgba(0,0,0,.25); margin:0 auto 20px; }
            .print-btn-bar { position:fixed; top:0; left:0; right:0; background:#1a1d26; padding:10px 20px; display:flex; align-items:center; gap:12px; z-index:999; box-shadow:0 2px 8px rgba(0,0,0,.4); font-family:Arial,sans-serif; }
            .print-btn-bar a { color:#8b949e; text-decoration:none; font-size:12px; }
            .print-btn-bar a:hover { color:#fff; }
            .btn-print { background:#00c3ff; color:#000; border:none; border-radius:6px; padding:7px 18px; font-size:12px; font-weight:bold; cursor:pointer; margin-left:auto; display:flex; align-items:center; gap:6px; }
            .page:first-child { margin-top:55px; }
        }
    </style>
</head>
<body>

<div class="print-btn-bar no-print">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="#e74c3c"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
    <span style="color:#fff;font-size:12px;font-weight:bold;">Note de Rejet — <?= htmlspecialchars($codification) ?></span>
    <a href="../../Controllers/Rejets/RejetController.php?page=details&id=<?= $id ?>">← Retour aux détails</a>
    <button class="btn-print" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        Imprimer / PDF
    </button>
</div>

<!-- PAGE 1 -->
<div class="page">
    <div class="doc-header">
        <div class="logo-block">
            <img src="<?= $logoUrl ?>" alt="Sonatrach">
            <div class="company-lines">Activité Exploration - Production<br>Direction Production<br>Direction Finance &amp; Comptabilité</div>
        </div>
        <div class="title-block"><h1>NOTE DE REJET DE FACTURES</h1></div>
        <div class="date-block">Alger, le <?= $dateRejet ?></div>
    </div>

    <div class="exp-dest-row">
        <div class="box-label"><div class="box-title">EXPÉDITEUR</div><div class="box-value">Règlement Étranger</div></div>
        <div class="box-label"><div class="box-title">DESTINATAIRE</div><div class="box-value"><?= htmlspecialchars($rejet['structure_nom'] ?? 'SIEGE-DP') ?></div></div>
    </div>

    <div class="ref-line"><strong>Réf N° :</strong>&nbsp;&nbsp; <?= htmlspecialchars($codification) ?></div>

    <div class="designation-box">
        <div class="desig-title">DÉSIGNATION</div>
        <div class="desig-body">
            <div class="intro-text">
                Nous vous retournons ci-joint le listing des factures relatives au contrat
                <strong><?= htmlspecialchars($rejet['Nom_Fournisseur'] ?? '') ?> <?= htmlspecialchars($rejet['num_Contrat'] ?? '') ?></strong>
            </div>
            <?php
            $cause = trim($rejet['cause'] ?? '');
            if ($cause):
                foreach (explode("\n", $cause) as $line):
                    $line = trim($line);
                    if ($line): ?>
                        <div class="motif-item">- <?= htmlspecialchars($line) ?></div>
                    <?php endif;
                endforeach;
            else: ?>
                <div class="motif-item">- motif du rejet</div>
            <?php endif; ?>
            <div class="closing">Bonne réception.</div>
        </div>
    </div>

    <div class="page-num">Page 1/2</div>
    <div class="doc-footer">
        ACTIVITÉ EXPLORATION &amp; PRODUCTION - DIVISION PRODUCTION - DIRECTION FINANCE ET COMPTABILITÉ<br>
        80, Chemin du Reservoir, 16160 Biger BP., 310 Alger, TEL: 021-47/1310 FAX: 021-47/0168
    </div>
</div>

<!-- PAGE 2 -->
<div class="page">
    <div class="small-header">
        <img src="<?= $logoUrl ?>" alt="Sonatrach">
        <span class="small-title">Direction Finance &amp; Comptabilité</span>
        <span class="small-date">Alger, le <?= $dateRejet ?></span>
    </div>

    <div class="exp-dest-row">
        <div class="box-label"><div class="box-title">EXPÉDITEUR</div><div class="box-value">Règlement Étranger</div></div>
        <div class="box-label"><div class="box-title">DESTINATAIRE</div><div class="box-value"><?= htmlspecialchars($rejet['structure_nom'] ?? 'SIEGE-DP') ?></div></div>
    </div>

    <div class="ref-line"><strong>Réf N° :</strong>&nbsp;&nbsp; <?= htmlspecialchars($codification) ?></div>

    <div class="section-title">LISTE DES FACTURES (PJ)</div>
    <table class="factures-table">
        <thead>
            <tr>
                <th style="width:30%;">N° Facture</th>
                <th style="width:25%;">Date</th>
                <th style="width:25%;">Montant</th>
                <th style="width:20%;">Devise</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($factures as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['Num_facture']) ?></td>
                    <td><?= !empty($f['date_facture']) ? (new DateTime($f['date_facture']))->format('d/m/Y') : '—' ?></td>
                    <td class="montant"><?= number_format(floatval($f['Montant']), 2, '.', ' ') ?></td>
                    <td><?= htmlspecialchars($f['monnaie_code']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($factures)): ?>
                <tr><td colspan="4" style="text-align:center;padding:6px;">Aucune facture</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signature-area">
        Reçu / Visa / Cachet : <span class="signature-line">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
    </div>

    <div class="page-num">Page 2/2</div>
    <div class="doc-footer">
        ACTIVITÉ EXPLORATION &amp; PRODUCTION - DIVISION PRODUCTION - DIRECTION FINANCE ET COMPTABILITÉ<br>
        80, Chemin du Reservoir, 16160 Biger BP., 310 Alger, TEL: 021-47/1310 FAX: 021-47/0168
    </div>
</div>

</body>
</html>