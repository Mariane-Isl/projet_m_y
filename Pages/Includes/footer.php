</div><!-- /main-content -->

<footer>
    &copy; <?= date('Y') ?> Direction des Projets &mdash; Tous droits réservés.
</footer>

<?php
$projectRoot = realpath(__DIR__ . '/../../');
$docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');
$jsBase      = str_replace('\\', '/', substr($projectRoot, strlen($docRoot))) . '/dist/js/';
?>
<script src="<?= $jsBase ?>jquery-3.7.0.min.js"></script>
<script src="<?= $jsBase ?>jquery.dataTables.min.js"></script>
<script src="<?= $jsBase ?>dataTables.bootstrap5.min.js"></script>
<script src="<?= $jsBase ?>bootstrap.bundle.min.js"></script>

<script>
// Date dans la topbar
(function () {
    var el = document.getElementById('currentDate');
    if (el) {
        el.textContent = new Date().toLocaleDateString('fr-DZ', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    }
})();

// Fermer les dropdowns en cliquant en dehors
document.addEventListener('click', function (e) {
    document.querySelectorAll('.brd-dropdown.open').forEach(function (dd) {
        if (!dd.contains(e.target)) dd.classList.remove('open');
    });
});

// Fonction globale d'ouverture/fermeture du dropdown
function toggleDD(id) {
    var dd = document.getElementById('dd-' + id);
    if (!dd) return;
    var isOpen = dd.classList.contains('open');
    document.querySelectorAll('.brd-dropdown.open').forEach(function (d) { d.classList.remove('open'); });
    if (!isOpen) dd.classList.add('open');
}
</script>

</body>
</html>
