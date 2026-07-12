<?php
/**
 * Semester Management – Admin
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');
$action    = $_GET['action'] ?? 'list';
$semId     = (int)($_GET['id'] ?? 0);
$pageTitle = 'Semester Management';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { setFlash('danger','CSRF error.'); redirect(APP_URL.'/admin/semesters.php'); }
    $f = $_POST;
    if (($f['_method'] ?? '') === 'DELETE') {
        dbExecute("DELETE FROM semesters WHERE id=?", [(int)$f['sem_id']]);
        setFlash('success','Deleted.'); redirect(APP_URL.'/admin/semesters.php');
    }
    
    $is_active = isset($f['is_active']) ? 1 : 0;
    if ($is_active) dbExecute("UPDATE semesters SET is_active=0 WHERE course_id=?", [(int)$f['course_id']]); // Only 1 active per course
    if (!empty($f['edit_id'])) {
        dbExecute("UPDATE semesters SET sem_number=?, sem_name=?, course_id=?, academic_year=?, start_date=?, end_date=?, is_active=? WHERE id=?", 
            [(int)$f['sem_number'], $f['sem_name'], (int)$f['course_id'], $f['academic_year'], $f['start_date'], $f['end_date'], $is_active, (int)$f['edit_id']]);
        setFlash('success','Updated.');
    } else {
        dbExecute("INSERT INTO semesters (sem_number, sem_name, course_id, academic_year, start_date, end_date, is_active) VALUES (?,?,?,?,?,?,?)", 
            [(int)$f['sem_number'], $f['sem_name'], (int)$f['course_id'], $f['academic_year'], $f['start_date'], $f['end_date'], $is_active]);
        setFlash('success','Added.');
    }
    redirect(APP_URL.'/admin/semesters.php');
}
$courses = dbFetchAll("SELECT id, course_name FROM courses WHERE status=1");
$sem = $semId ? dbFetchOne("SELECT * FROM semesters WHERE id=?", [$semId]) : null;
$semesters = dbFetchAll("SELECT s.*, c.course_name FROM semesters s JOIN courses c ON s.course_id=c.id ORDER BY s.id DESC");
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1 class="page-title"><?= $action==='add'?'Add Semester':($action==='edit'?'Edit Semester':'Semesters') ?></h1>
  <?php if($action==='list'): ?><a href="?action=add" class="btn btn-erp-primary">Add Semester</a><?php else: ?><a href="?" class="btn btn-outline-secondary">Back</a><?php endif; ?>
</div>
<?php if ($action==='add' || $action==='edit'): ?>
<div class="erp-card">
  <div class="erp-card-body">
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
      <?php if($action==='edit'): ?><input type="hidden" name="edit_id" value="<?= $sem['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="erp-form-label">Semester Number *</label><input type="number" name="sem_number" class="erp-form-control" required value="<?= esc($sem['sem_number']??1) ?>"></div>
        <div class="col-md-6"><label class="erp-form-label">Semester Name *</label><input type="text" name="sem_name" class="erp-form-control" required value="<?= esc($sem['sem_name']??'') ?>"></div>
        <div class="col-md-6"><label class="erp-form-label">Course *</label>
          <select name="course_id" class="erp-form-select" required><?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>" <?= ($sem['course_id']??0)==$c['id']?'selected':'' ?>><?= esc($c['course_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="erp-form-label">Academic Year *</label><input type="text" name="academic_year" class="erp-form-control" required placeholder="e.g. 2024-25" value="<?= esc($sem['academic_year']??'') ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">Start Date</label><input type="date" name="start_date" class="erp-form-control" value="<?= esc($sem['start_date']??'') ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">End Date</label><input type="date" name="end_date" class="erp-form-control" value="<?= esc($sem['end_date']??'') ?>"></div>
        <div class="col-md-4 pt-4"><input type="checkbox" name="is_active" <?= !empty($sem['is_active'])?'checked':'' ?>> Set as Active Semester</div>
        <div class="col-12 mt-3"><button type="submit" class="btn btn-erp-primary">Save</button></div>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<div class="erp-card">
  <div class="erp-card-body p-0">
    <table class="erp-table">
      <thead><tr><th>Sem Num</th><th>Name</th><th>Course</th><th>Academic Year</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($semesters as $s): ?>
        <tr>
          <td><?= esc($s['sem_number']) ?></td><td><?= esc($s['sem_name']) ?></td><td><?= esc($s['course_name']) ?></td>
          <td><?= esc($s['academic_year']) ?></td>
          <td><span class="status-badge <?= $s['is_active']?'badge-present':'badge-absent' ?>"><?= $s['is_active']?'Active':'Inactive' ?></span></td>
          <td>
            <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="POST" id="del-<?= $s['id'] ?>" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
              <input type="hidden" name="_method" value="DELETE"><input type="hidden" name="sem_id" value="<?= $s['id'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWithConfirm('del-<?= $s['id'] ?>','Delete?')"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
