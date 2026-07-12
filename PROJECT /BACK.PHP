<?php
/**
 * Backup Database – Admin
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');
$pageTitle = 'Database Backup';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup'])) {
    if (!validateCsrf()) { setFlash('danger','CSRF error.'); redirect(APP_URL.'/admin/backup.php'); }
    $file = 'backup_' . date('Ymd_His') . '.sql';
    $dir = __DIR__ . '/../assets/backups/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $path = $dir . $file;
    // Attempting mysqldump
    $cmd = "mysqldump -u " . DB_USER . (DB_PASS ? " -p'" . DB_PASS . "'" : "") . " " . DB_NAME . " > " . $path;
    exec($cmd, $output, $return_var);
    if ($return_var === 0) setFlash('success', 'Backup created successfully: ' . $file);
    else setFlash('danger', 'Automated backup failed. Use manual phpMyAdmin export.');
    redirect(APP_URL.'/admin/backup.php');
}
$backups = [];
$dir = __DIR__ . '/../assets/backups/';
if (is_dir($dir)) {
    foreach(scandir($dir) as $f) {
        if ($f!=='.' && $f!=='..' && pathinfo($f, PATHINFO_EXTENSION)==='sql') $backups[] = $f;
    }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1 class="page-title">Backup & Restore</h1></div>
<div class="row">
    <div class="col-md-6">
        <div class="erp-card mb-3">
            <div class="erp-card-header"><h2 class="card-title">Automated Backup</h2></div>
            <div class="erp-card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
                    <input type="hidden" name="backup" value="1">
                    <button class="btn btn-erp-primary"><i class="bi bi-cloud-arrow-down me-2"></i>Create Database Backup</button>
                </form>
                <div class="mt-3">
                    <h6>Available Backups:</h6>
                    <ul>
                        <?php foreach($backups as $b): ?><li><?= esc($b) ?></li><?php endforeach; ?>
                        <?php if(!$backups) echo '<li>None</li>'; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="erp-card">
            <div class="erp-card-header"><h2 class="card-title">Manual Instructions</h2></div>
            <div class="erp-card-body">
                <p>If automated backup fails (exec() disabled on server):</p>
                <ol>
                    <li>Open phpMyAdmin.</li>
                    <li>Select the database <code><?= DB_NAME ?></code>.</li>
                    <li>Go to the <b>Export</b> tab.</li>
                    <li>Click <b>Go</b> to download the SQL file.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
