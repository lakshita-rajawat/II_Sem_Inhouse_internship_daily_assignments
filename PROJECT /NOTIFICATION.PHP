<?php
/**
 * Notifications Management – Admin
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');
$pageTitle = 'Notifications Management';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { setFlash('danger','CSRF error.'); redirect(APP_URL.'/admin/notifications.php'); }
    $f = $_POST;
    if (($f['_method'] ?? '') === 'DELETE') {
        dbExecute("DELETE FROM notifications WHERE id=?", [(int)$f['notif_id']]);
        setFlash('success','Deleted.'); redirect(APP_URL.'/admin/notifications.php');
    }
    $title = trim($f['title'] ?? '');
    $msg = trim($f['message'] ?? '');
    $type = $f['type'] ?? 'Info';
    $target = $f['target_role'] ?? 'all';
    $active = isset($f['is_active']) ? 1 : 0;
    
    dbExecute("INSERT INTO notifications (title, message, type, target_role, is_active, created_by) VALUES (?,?,?,?,?,?)",
        [$title, $msg, $type, $target, $active, currentUserId()]);
    setFlash('success','Added.');
    redirect(APP_URL.'/admin/notifications.php');
}
$notifications = dbFetchAll("SELECT * FROM notifications ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1 class="page-title">Notifications</h1></div>
<div class="row g-3">
  <div class="col-md-4">
    <div class="erp-card">
      <div class="erp-card-header"><h2 class="card-title">Add Notification</h2></div>
      <div class="erp-card-body">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
          <div class="mb-3"><label class="erp-form-label">Title *</label><input type="text" name="title" class="erp-form-control" required></div>
          <div class="mb-3"><label class="erp-form-label">Message *</label><textarea name="message" class="erp-form-control" rows="3" required></textarea></div>
          <div class="mb-3"><label class="erp-form-label">Type</label>
            <select name="type" class="erp-form-select">
              <?php foreach(['Info','Warning','Success','Danger','Exam','Holiday','General'] as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="erp-form-label">Target Role</label>
            <select name="target_role" class="erp-form-select"><option value="all">All</option><option value="admin">Admin</option><option value="teacher">Teacher</option><option value="student">Student</option></select>
          </div>
          <div class="mb-3"><input type="checkbox" name="is_active" checked> Active</div>
          <button type="submit" class="btn btn-erp-primary w-100">Post Notification</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="erp-card">
      <div class="erp-card-body p-0">
        <table class="erp-table">
          <thead><tr><th>Title</th><th>Target</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach($notifications as $n): ?>
            <tr>
              <td><?= esc($n['title']) ?></td><td><?= esc($n['target_role']) ?></td><td><?= esc($n['type']) ?></td>
              <td><span class="status-badge <?= $n['is_active']?'badge-present':'badge-absent' ?>"><?= $n['is_active']?'Active':'Inactive' ?></span></td>
              <td>
                <form method="POST" id="del-<?= $n['id'] ?>" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
                  <input type="hidden" name="_method" value="DELETE"><input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWithConfirm('del-<?= $n['id'] ?>','Delete?')"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
