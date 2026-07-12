<?php
/**
 * Department Management – Admin
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');
$action    = $_GET['action'] ?? 'list';
$deptId = (int)($_GET['id'] ?? 0);
$pageTitle = 'Department Management';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { setFlash('danger','CSRF error.'); redirect(APP_URL.'/admin/departments.php'); }
    $f = $_POST;
    if (($f['_method'] ?? '') === 'DELETE') {
        dbExecute("DELETE FROM departments WHERE id=?", [(int)$f['dept_id']]);
        setFlash('success','Deleted.'); redirect(APP_URL.'/admin/departments.php');
    }
    
    if (!empty($f['edit_id'])) {
        dbExecute("UPDATE departments SET dept_code=?, dept_name=?, hod=? WHERE id=?", [$f['dept_code'], $f['dept_name'], $f['hod'], (int)$f['edit_id']]);
        setFlash('success','Updated.');
    } else {
        dbExecute("INSERT INTO departments (dept_code, dept_name, hod) VALUES (?,?,?)", [$f['dept_code'], $f['dept_name'], $f['hod']]);
        setFlash('success','Added.');
    }
    redirect(APP_URL.'/admin/departments.php');
}
$dept = $deptId ? dbFetchOne("SELECT * FROM departments WHERE id=?", [$deptId]) : null;
$depts = dbFetchAll("SELECT * FROM departments ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1 class="page-title"><?= $action==='add'?'Add Department':($action==='edit'?'Edit Department':'Departments') ?></h1>
  <?php if($action==='list'): ?><a href="?action=add" class="btn btn-erp-primary">Add Department</a><?php else: ?><a href="?" class="btn btn-outline-secondary">Back</a><?php endif; ?>
</div>
<?php if ($action==='add' || $action==='edit'): ?>
<div class="erp-card">
  <div class="erp-card-body">
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
      <?php if($action==='edit'): ?><input type="hidden" name="edit_id" value="<?= $dept['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-4"><label class="erp-form-label">Code *</label><input type="text" name="dept_code" class="erp-form-control" required value="<?= esc($dept['dept_code']??'') ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">Name *</label><input type="text" name="dept_name" class="erp-form-control" required value="<?= esc($dept['dept_name']??'') ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">HOD</label><input type="text" name="hod" class="erp-form-control" value="<?= esc($dept['hod']??'') ?>"></div>
        <div class="col-12 mt-3"><button type="submit" class="btn btn-erp-primary">Save</button></div>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<div class="erp-card">
  <div class="erp-card-body p-0">
    <table class="erp-table">
      <thead><tr><th>Code</th><th>Name</th><th>HOD</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($depts as $d): ?>
        <tr>
          <td><?= esc($d['dept_code']) ?></td><td><?= esc($d['dept_name']) ?></td><td><?= esc($d['hod']) ?></td>
          <td>
            <a href="?action=edit&id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="POST" id="del-<?= $d['id'] ?>" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
              <input type="hidden" name="_method" value="DELETE"><input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWithConfirm('del-<?= $d['id'] ?>','Delete?')"><i class="bi bi-trash"></i></button>
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
