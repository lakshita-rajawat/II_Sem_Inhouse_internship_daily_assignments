<?php
/**
 * Exam Management – Create/List
 */
require_once __DIR__ . '/../../includes/auth.php';
requireLogin('admin');
$action = $_GET['action'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { setFlash('danger','CSRF error.'); redirect(APP_URL.'/modules/exams/list.php'); }
    $f = $_POST;
    if (($f['_method'] ?? '') === 'DELETE') {
        dbExecute("DELETE FROM exams WHERE id=?", [(int)$f['exam_id']]);
        setFlash('success','Deleted.'); redirect(APP_URL.'/modules/exams/list.php');
    }
    dbExecute("INSERT INTO exams (exam_name, exam_type, course_id, sem_number, academic_year, start_date, end_date, status, created_by) VALUES (?,?,?,?,?,?,?,?,?)",
        [$f['exam_name'], $f['exam_type'], (int)$f['course_id'], (int)$f['sem_number'], $f['academic_year'], $f['start_date'], $f['end_date'], $f['status'], currentUserId()]);
    setFlash('success','Exam Scheduled.');
    redirect(APP_URL.'/modules/exams/list.php');
}
$courses = dbFetchAll("SELECT id, course_name FROM courses WHERE status=1");
$exams = dbFetchAll("SELECT e.*, c.course_name FROM exams e JOIN courses c ON e.course_id=c.id ORDER BY e.id DESC");
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1 class="page-title">Exams</h1><?php if($action==='list'): ?><a href="?action=add" class="btn btn-erp-primary">Schedule Exam</a><?php else: ?><a href="?" class="btn btn-outline-secondary">Back</a><?php endif; ?></div>
<?php if ($action==='add'): ?>
<div class="erp-card"><div class="erp-card-body">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
        <div class="row g-3">
            <div class="col-md-6"><label class="erp-form-label">Exam Name *</label><input type="text" name="exam_name" class="erp-form-control" required></div>
            <div class="col-md-6"><label class="erp-form-label">Type</label><select name="exam_type" class="erp-form-select"><?php foreach(['MidSem','EndSem','Practical','Viva','Assignment'] as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="erp-form-label">Course *</label><select name="course_id" class="erp-form-select" required><?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>"><?= esc($c['course_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="erp-form-label">Semester *</label><input type="number" name="sem_number" class="erp-form-control" required></div>
            <div class="col-md-4"><label class="erp-form-label">Academic Year</label><input type="text" name="academic_year" class="erp-form-control" required placeholder="2025-26"></div>
            <div class="col-md-4"><label class="erp-form-label">Start Date</label><input type="date" name="start_date" class="erp-form-control"></div>
            <div class="col-md-4"><label class="erp-form-label">End Date</label><input type="date" name="end_date" class="erp-form-control"></div>
            <div class="col-md-4"><label class="erp-form-label">Status</label><select name="status" class="erp-form-select"><option value="Scheduled">Scheduled</option><option value="Ongoing">Ongoing</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select></div>
            <div class="col-12 mt-3"><button type="submit" class="btn btn-erp-primary">Schedule</button></div>
        </div>
    </form>
</div></div>
<?php else: ?>
<div class="erp-card"><div class="erp-card-body p-0"><table class="erp-table">
    <thead><tr><th>Name</th><th>Type</th><th>Course / Sem</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
        <?php foreach($exams as $e): ?>
        <tr>
            <td><?= esc($e['exam_name']) ?></td><td><?= esc($e['exam_type']) ?></td><td><?= esc($e['course_name']) ?> (Sem <?= $e['sem_number'] ?>)</td>
            <td><?= esc($e['start_date']) ?> to <?= esc($e['end_date']) ?></td><td><span class="status-badge <?= $e['status']=='Completed'?'badge-present':'badge-leave' ?>"><?= esc($e['status']) ?></span></td>
            <td>
                <form method="POST" id="del-<?= $e['id'] ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWithConfirm('del-<?= $e['id'] ?>','Delete?')"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table></div></div>
<?php endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
