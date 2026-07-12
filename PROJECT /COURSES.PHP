<?php
/**
 * Course Management – Admin
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');
$action    = $_GET['action'] ?? 'list';
$courseId  = (int)($_GET['id'] ?? 0);
$pageTitle = 'Course Management';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { setFlash('danger','CSRF error.'); redirect(APP_URL.'/admin/courses.php'); }
    $f = $_POST;
    if (($f['_method'] ?? '') === 'DELETE') {
        dbExecute("DELETE FROM courses WHERE id=?", [(int)$f['course_id']]);
        setFlash('success','Deleted.'); redirect(APP_URL.'/admin/courses.php');
    }
    
    if (!empty($f['edit_id'])) {
        dbExecute("UPDATE courses SET course_code=?, course_name=?, dept_id=?, duration_years=?, degree_type=?, total_semesters=? WHERE id=?", 
            [$f['course_code'], $f['course_name'], (int)$f['dept_id'], (int)$f['duration_years'], $f['degree_type'], (int)$f['total_semesters'], (int)$f['edit_id']]);
        setFlash('success','Updated.');
    } else {
        dbExecute("INSERT INTO courses (course_code, course_name, dept_id, duration_years, degree_type, total_semesters) VALUES (?,?,?,?,?,?)", 
            [$f['course_code'], $f['course_name'], (int)$f['dept_id'], (int)$f['duration_years'], $f['degree_type'], (int)$f['total_semesters']]);
        setFlash('success','Added.');
    }
    redirect(APP_URL.'/admin/courses.php');
}
$depts = dbFetchAll("SELECT id, dept_name FROM departments WHERE status=1");
$course = $courseId ? dbFetchOne("SELECT * FROM courses WHERE id=?", [$courseId]) : null;
$courses = dbFetchAll("SELECT c.*, d.dept_name FROM courses c JOIN departments d ON c.dept_id=d.id ORDER BY c.id DESC");
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1 class="page-title"><?= $action==='add'?'Add Course':($action==='edit'?'Edit Course':'Courses') ?></h1>
  <?php if($action==='list'): ?><a href="?action=add" class="btn btn-erp-primary">Add Course</a><?php else: ?><a href="?" class="btn btn-outline-secondary">Back</a><?php endif; ?>
</div>
<?php if ($action==='add' || $action==='edit'): ?>
<div class="erp-card">
  <div class="erp-card-body">
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
      <?php if($action==='edit'): ?><input type="hidden" name="edit_id" value="<?= $course['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="erp-form-label">Course Code *</label><input type="text" name="course_code" class="erp-form-control" required value="<?= esc($course['course_code']??'') ?>"></div>
        <div class="col-md-6"><label class="erp-form-label">Course Name *</label><input type="text" name="course_name" class="erp-form-control" required value="<?= esc($course['course_name']??'') ?>"></div>
        <div class="col-md-6"><label class="erp-form-label">Department *</label>
          <select name="dept_id" class="erp-form-select" required><?php foreach($depts as $d): ?><option value="<?= $d['id'] ?>" <?= ($course['dept_id']??0)==$d['id']?'selected':'' ?>><?= esc($d['dept_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="erp-form-label">Degree Type</label>
          <select name="degree_type" class="erp-form-select"><option value="UG" <?= ($course['degree_type']??'')=='UG'?'selected':'' ?>>UG</option><option value="PG" <?= ($course['degree_type']??'')=='PG'?'selected':'' ?>>PG</option></select></div>
        <div class="col-md-6"><label class="erp-form-label">Duration (Years)</label><input type="number" name="duration_years" class="erp-form-control" value="<?= esc($course['duration_years']??4) ?>"></div>
        <div class="col-md-6"><label class="erp-form-label">Total Semesters</label><input type="number" name="total_semesters" class="erp-form-control" value="<?= esc($course['total_semesters']??8) ?>"></div>
        <div class="col-12 mt-3"><button type="submit" class="btn btn-erp-primary">Save Course</button></div>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<div class="erp-card">
  <div class="erp-card-body p-0">
    <table class="erp-table">
      <thead><tr><th>Code</th><th>Name</th><th>Dept</th><th>Duration</th><th>Semesters</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($courses as $c): ?>
        <tr>
          <td><?= esc($c['course_code']) ?></td><td><?= esc($c['course_name']) ?></td><td><?= esc($c['dept_name']) ?></td>
          <td><?= esc($c['duration_years']) ?> Years</td><td><?= esc($c['total_semesters']) ?></td>
          <td>
            <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="POST" id="del-<?= $c['id'] ?>" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
              <input type="hidden" name="_method" value="DELETE"><input type="hidden" name="course_id" value="<?= $c['id'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWithConfirm('del-<?= $c['id'] ?>','Delete?')"><i class="bi bi-trash"></i></button>
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
