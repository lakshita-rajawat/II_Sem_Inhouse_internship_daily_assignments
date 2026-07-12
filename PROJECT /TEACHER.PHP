?php
/**
 * Teacher Management – Admin
 * Full CRUD for Teachers.
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');
$action    = $_GET['action'] ?? 'list';
$teacherId = (int)($_GET['id'] ?? 0);
$pageTitle = 'Teacher Management';
$breadcrumbs = [['label'=>'Admin','url'=>APP_URL.'/admin/dashboard.php'],['label'=>'Teachers']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { setFlash('danger','CSRF validation failed.'); redirect(APP_URL.'/admin/teachers.php'); }
    
    $f = $_POST;
    if (($f['_method'] ?? '') === 'DELETE') {
        $id = (int)($f['teacher_id'] ?? 0);
        dbExecute("DELETE FROM teachers WHERE id=?", [$id]);
        setFlash('success','Teacher deleted successfully.');
        redirect(APP_URL.'/admin/teachers.php');
    }
    $name     = trim($f['name'] ?? '');
    $email    = strtolower(trim($f['email'] ?? ''));
    $phone    = trim($f['phone'] ?? '');
    $dept     = (int)($f['dept_id'] ?? 0);
    $gender   = $f['gender'] ?? 'Male';
    $dob      = $f['dob'] ?? null;
    $qual     = trim($f['qualification'] ?? '');
    $desig    = trim($f['designation'] ?? '');
    $address  = trim($f['address'] ?? '');
    $joinDate = $f['joining_date'] ?? null;
    $status   = (int)($f['status'] ?? 1);
    $password = trim($f['password'] ?? '');
    if (empty($name) || empty($email) || empty($dept)) {
        setFlash('danger','Name, Email and Department are required.');
        redirect(APP_URL.'/admin/teachers.php?action='.($f['edit_id']?'edit':'add').'&id='.($f['edit_id']??0));
    }
    if (!empty($f['edit_id'])) {
        $eid = (int)$f['edit_id'];
        dbExecute(
            "UPDATE teachers SET name=?, email=?, phone=?, dept_id=?, gender=?, dob=?, qualification=?, designation=?, address=?, joining_date=?, status=? WHERE id=?",
            [$name, $email, $phone, $dept, $gender, $dob, $qual, $desig, $address, $joinDate, $status, $eid]
        );
        if ($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
            $t = dbFetchOne("SELECT user_id FROM teachers WHERE id=?",[$eid]);
            if ($t) dbExecute("UPDATE users SET password=? WHERE id=?",[$hash,$t['user_id']]);
        }
        setFlash('success','Teacher updated successfully.');
    } else {
        $passHash = password_hash($password ?: 'Teacher@123', PASSWORD_BCRYPT, ['cost'=>12]);
        $maxId    = dbFetchOne("SELECT MAX(id) mx FROM teachers")['mx'] ?? 0;
        $tId      = 'TCH-' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
        
        $userId = dbExecute("INSERT INTO users(name,email,password,role) VALUES(?,?,?,'teacher')", [$name, $email, $passHash]);
        dbExecute(
            "INSERT INTO teachers (user_id, teacher_id, name, dept_id, email, phone, gender, dob, qualification, designation, address, joining_date, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$userId, $tId, $name, $dept, $email, $phone, $gender, $dob, $qual, $desig, $address, $joinDate, $status]
        );
        setFlash('success','Teacher added successfully.');
    }
    redirect(APP_URL.'/admin/teachers.php');
}
$depts = dbFetchAll("SELECT id, dept_name FROM departments WHERE status=1 ORDER BY dept_name");
$teacher = null;
if (in_array($action,['view','edit']) && $teacherId) {
    $teacher = dbFetchOne("SELECT t.*, d.dept_name FROM teachers t JOIN departments d ON d.id=t.dept_id WHERE t.id=?", [$teacherId]);
    if (!$teacher) { setFlash('danger','Not found.'); redirect(APP_URL.'/admin/teachers.php'); }
}
$search = trim($_GET['q'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (t.name LIKE ? OR t.teacher_id LIKE ? OR t.email LIKE ?)";
    $params = array_fill(0, 3, "%$search%");
}
$teachers = dbFetchAll("SELECT t.*, d.dept_name FROM teachers t JOIN departments d ON d.id=t.dept_id $where ORDER BY t.id DESC", $params);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><?= $action==='add'?'Add Teacher':($action==='edit'?'Edit Teacher':($action==='view'?'View Teacher':'Teachers')) ?></h1>
  </div>
  <?php if($action==='list'): ?>
    <a href="?action=add" class="btn btn-erp-primary"><i class="bi bi-plus-lg me-2"></i>Add Teacher</a>
  <?php else: ?>
    <a href="?" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-2"></i>Back</a>
  <?php endif; ?>
</div>
<?php if ($action==='add' || $action==='edit'): ?>
<div class="erp-card">
  <div class="erp-card-body">
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
      <?php if($action==='edit'): ?><input type="hidden" name="edit_id" value="<?= $teacher['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="erp-form-label">Name *</label><input type="text" name="name" class="erp-form-control" required value="<?= esc($teacher['name']??'') ?>"></div>
        <div class="col-md-6"><label class="erp-form-label">Email *</label><input type="email" name="email" class="erp-form-control" required value="<?= esc($teacher['email']??'') ?>"></div>
        <div class="col-md-6"><label class="erp-form-label">Department *</label>
          <select name="dept_id" class="erp-form-select" required>
            <?php foreach($depts as $d): ?><option value="<?= $d['id'] ?>" <?= ($teacher['dept_id']??0)==$d['id']?'selected':'' ?>><?= esc($d['dept_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="erp-form-label">Phone</label><input type="text" name="phone" class="erp-form-control" value="<?= esc($teacher['phone']??'') ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">Designation</label><input type="text" name="designation" class="erp-form-control" value="<?= esc($teacher['designation']??'') ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">Qualification</label><input type="text" name="qualification" class="erp-form-control" value="<?= esc($teacher['qualification']??'') ?>"></div>
        <div class="col-md-4"><label class="erp-form-label">Status</label>
          <select name="status" class="erp-form-select"><option value="1" <?= ($teacher['status']??1)==1?'selected':'' ?>>Active</option><option value="0" <?= ($teacher['status']??1)==0?'selected':'' ?>>Inactive</option></select>
        </div>
        <div class="col-12 mt-3"><button type="submit" class="btn btn-erp-primary">Save Teacher</button></div>
      </div>
    </form>
  </div>
</div>
<?php elseif ($action==='view'): ?>
<?php 
    $assigned_subjects = dbFetchAll("SELECT sub_code, sub_name FROM subjects WHERE teacher_id=?", [$teacher['id']]); 
?>
<div class="erp-card">
  <div class="erp-card-body">
    <h5><?= esc($teacher['name']) ?> (<?= esc($teacher['teacher_id']) ?>)</h5>
    <p><strong>Dept:</strong> <?= esc($teacher['dept_name']) ?> | <strong>Email:</strong> <?= esc($teacher['email']) ?></p>
    <hr>
    <h6>Assigned Subjects</h6>
    <ul>
        <?php foreach($assigned_subjects as $s): ?>
            <li><?= esc($s['sub_code'] . ' - ' . $s['sub_name']) ?></li>
        <?php endforeach; if(!$assigned_subjects) echo "<li>None assigned.</li>"; ?>
    </ul>
  </div>
</div>
<?php else: ?>
<div class="erp-card">
  <div class="erp-card-body p-0">
    <table class="erp-table">
      <thead><tr><th>ID</th><th>Name</th><th>Dept</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($teachers as $t): ?>
        <tr>
          <td><?= esc($t['teacher_id']) ?></td>
          <td><?= esc($t['name']) ?></td>
          <td><?= esc($t['dept_name']) ?></td>
          <td><?= esc($t['email']) ?></td>
          <td><?= $t['status']?'Active':'Inactive' ?></td>
          <td>
            <a href="?action=view&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
            <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="POST" id="del-<?= $t['id'] ?>" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
              <input type="hidden" name="_method" value="DELETE"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWithConfirm('del-<?= $t['id'] ?>','Delete?')"><i class="bi bi-trash"></i></button>
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
