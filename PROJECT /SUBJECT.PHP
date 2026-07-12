?php
/**
 * Subject Management – Admin
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');
$action    = $_GET['action'] ?? 'list';
$subjectId = (int)($_GET['id'] ?? 0);
$pageTitle = 'Subject Management';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { setFlash('danger','CSRF error.'); redirect(APP_URL.'/admin/subjects.php'); }
    
    $f = $_POST;
    if (($f['_method'] ?? '') === 'DELETE') {
        dbExecute("DELETE FROM subjects WHERE id=?", [(int)$f['subject_id']]);
        setFlash('success','Subject deleted.');
        redirect(APP_URL.'/admin/subjects.php');
    }
    $sub_code = trim($f['sub_code'] ?? '');
    $sub_name = trim($f['sub_name'] ?? '');
    $course_id = (int)$f['course_id'];
    $dept_id = (int)$f['dept_id'];
    $sem_number = (int)$f['sem_number'];
    $credits = (int)$f['credits'];
    $sub_type = $f['sub_type'] ?? 'Theory';
    $teacher_id = !empty($f['teacher_id']) ? (int)$f['teacher_id'] : null;
    if (!empty($f['edit_id'])) {
        dbExecute("UPDATE subjects SET sub_code=?, sub_name=?, course_id=?, dept_id=?, sem_number=?, credits=?, sub_type=?, teacher_id=? WHERE id=?",
            [$sub_code, $sub_name, $course_id, $dept_id, $sem_number, $credits, $sub_type, $teacher_id, (int)$f['edit_id']]);
        setFlash('success','Updated.');
    } else {
        dbExecute("INSERT INTO subjects (sub_code, sub_name, course_id, dept_id, sem_number, credits, sub_type, teacher_id) VALUES (?,?,?,?,?,?,?,?)",
            [$sub_code, $sub_name, $course_id, $dept_id, $sem_number, $credits, $sub_type, $teacher_id]);
        setFlash('success','Added.');
    }
    redirect(APP_URL.'/admin/subjects.php');
}
$courses = dbFetchAll("SELECT id, course_name FROM courses WHERE status=1");
$depts = dbFetchAll("SELECT id, dept_name FROM departments WHERE status=1");
$teachers = dbFetchAll("SELECT id, name FROM teachers WHERE status=1");
$subject = $subjectId ? dbFetchOne("SELECT * FROM subjects WHERE id=?", [$subjectId]) : null;
$subjects = dbFetchAll("SELECT s.*, c.course_name, t.name as teacher_name FROM subjects s LEFT JOIN courses c ON s.course_id=c.id LEFT JOIN teachers t ON s.teacher_id=t.id ORDER BY s.id DESC");
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1 class="page-title"><?= $action==='add'?'Add Subject':($action==='edit'?'Edit Subject':'Subjects') ?></h1>
  <?php if($action==='list'): ?><a href="?action=add" class="btn btn-erp-primary">Add Subject</a><?php else: ?><a href="?" class="btn btn-outline-secondary">Back</a><?php endif; ?>
</div>
<?php if ($action==='add' || $action==='edit'): ?>
<div class="erp-card">
  <div class="erp-card-body">
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
      <?php if($action==='edit'): ?><input type="hidden" name="edit_id" value="<?= $subject['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="erp-form-label">Subject Code *</label><input type="text" name="sub_code" class="erp-form-control" required value="<?= esc($subject['sub_code']??'') ?>"></div>
        <div class="col-md-6"><label class="erp-form-label">Subject Name *</label><input type="text" name="sub_name" class="erp-form-control" required value="<?= esc($subject['sub_name']??'') ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">Course *</label>
          <select name="course_id" class="erp-form-select" required><?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>" <?= ($subject['course_id']??0)==$c['id']?'selected':'' ?>><?= esc($c['course_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="erp-form-label">Department *</label>
          <select name="dept_id" class="erp-form-select" required><?php foreach($depts as $d): ?><option value="<?= $d['id'] ?>" <?= ($subject['dept_id']??0)==$d['id']?'selected':'' ?>><?= esc($d['dept_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="erp-form-label">Semester *</label><input type="number" name="sem_number" class="erp-form-control" required value="<?= esc($subject['sem_number']??1) ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">Credits</label><input type="number" name="credits" class="erp-form-control" value="<?= esc($subject['credits']??3) ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">Teacher</label>
          <select name="teacher_id" class="erp-form-select"><option value="">None</option><?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>" <?= ($subject['teacher_id']??0)==$t['id']?'selected':'' ?>><?= esc($t['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12 mt-3"><button type="submit" class="btn btn-erp-primary">Save Subject</button></div>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<div class="erp-card">
  <div class="erp-card-body p-0">
    <table class="erp-table">
      <thead><tr><th>Code</th><th>Name</th><th>Course</th><th>Sem</th><th>Teacher</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($subjects as $s): ?>
        <tr>
          <td><?= esc($s['sub_code']) ?></td><td><?= esc($s['sub_name']) ?></td><td><?= esc($s['course_name']) ?></td><td><?= esc($s['sem_number']) ?></td><td><?= esc($s['teacher_name']?:'-') ?></td>
          <td>
            <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="POST" id="del-<?= $s['id'] ?>" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
              <input type="hidden" name="_method" value="DELETE"><input type="hidden" name="subject_id" value="<?= $s['id'] ?>">
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
