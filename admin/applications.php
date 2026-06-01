<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$applicationTab = $request->get('tab', 'themes');
$applicationTabs = ['themes', 'plugins', 'languages'];

if (!in_array($applicationTab, $applicationTabs, true)) {
    $applicationTab = 'themes';
}
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <?php include 'application-tabs.php'; ?>
        <?php include __DIR__ . '/applications/' . $applicationTab . '.php'; ?>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
if (!empty($applicationDeleteConfirm)): ?>
<script>
    $('.operate-delete').click(function () {
        var message = $(this).attr('lang');
        return !message || confirm(message);
    });
</script>
<?php endif;
include 'footer.php';
?>
