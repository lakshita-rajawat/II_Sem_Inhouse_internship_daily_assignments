<?php
/**
 * Marks Entry – Admin & Teacher
 */
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(); 
$pageTitle = 'Enter Marks';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { setFlash('danger','CSRF error.'); redirect(APP_URL.'/modules/marks/entry.php'); }
    $exam = (int)$_POST['exam_id'];
    $sub = (int)$_POST['subject_id'];
    $marks = $_POST['marks'] ?? [];
    
    foreach($marks as $student_id => $m) {
        $int = (float)($m['int'] ?? 0);
        $ext = (float)($m['ext'] ?? 0);
        $prac = (float)($m['prac'] ?? 0);
        $tot = $int + $ext + $prac;
        // Simple grade calc
        $grade = 'F'; $gp = 0; $res = 'Fail';
        if ($tot >= 90) { $grade='O'; $gp=10; $res='Pass'; }
        elseif ($tot >= 80) { $grade='A+'; $gp=9; $res='Pass'; }
        elseif ($tot >= 70) { $grade='A'; $gp=8; $res='Pass'; }
        elseif ($tot >= 60) { $grade='B+'; $gp=7; $res='Pass'; }
        elseif ($tot >= 50) { $grade='B'; $gp=6; $res='Pass'; }
        elseif ($tot >= 40) { $grade='C'; $gp=5; $res='Pass'; }
        
        dbExecute("INSERT INTO marks (student_id, subject_id, exam_id, internal_marks, external_marks, practical_marks, grade, grade_points, result, entered_by) 
            VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE internal_marks=VALUES(internal_marks), external_marks=VALUES(external_marks), practical_marks=VALUES(practical_marks), grade=VALUES(grade), grade_points=VALUES(grade_points), result=VALUES(result), entered_by=VALUES(entered_by)",
            [$student_id, $sub, $exam, $int, $ext, $prac, $grade, $gp, $res, currentUserId()]);
    }
    setFlash('success','Marks saved.');
    redirect(APP_URL.'/modules/marks/entry.php?exam='.$exam.'&sub='.$sub);
}
$exams = dbFetchAll("SELECT * FROM exams WHERE status IN ('Scheduled','Ongoing','Completed')");
$subjects = dbFetchAll("SELECT id, sub_code, sub_name FROM subjects");
$selExam = (int)($_GET['exam'] ?? 0);
$selSub = (int)($_GET['sub'] ?? 0);
$students = [];
if ($selExam && $selSub) {
    $e = dbFetchOne("SELECT course_id, sem_number FROM exams WHERE id=?", [$selExam]);
    $students = dbFetchAll("SELECT s.id, s.name, s.roll_number, m.internal_marks, m.external_marks, m.practical_marks FROM students s LEFT JOIN marks m ON m.student_id=s.id AND m.subject_id=? AND m.exam_id=? WHERE s.course_id=? AND s.current_sem=? AND s.status='Active'", [$selSub, $selExam, $e['course_id'], $e['sem_number']]);
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1 class="page-title">Enter Marks</h1></div>
<div class="erp-card mb-4"><div class="erp-card-body">
    <form method="GET" class="row g-3">
        <div class="col-md-5"><label class="erp-form-label">Exam</label><select name="exam" class="erp-form-select" required><option value="">Select Exam</option><?php foreach($exams as $e): ?><option value="<?= $e['id'] ?>" <?= $selExam==$e['id']?'selected':'' ?>><?= esc($e['exam_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-5"><label class="erp-form-label">Subject</label><select name="sub" class="erp-form-select" required><option value="">Select Subject</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $selSub==$s['id']?'selected':'' ?>><?= esc($s['sub_code'].' - '.$s['sub_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-erp-primary w-100">Load Students</button></div>
    </form>
</div></div>
<?php if($students): ?>
<div class="erp-card"><div class="erp-card-body p-0">
    <form method="POST" class="p-3">
        <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>"><input type="hidden" name="exam_id" value="<?= $selExam ?>"><input type="hidden" name="subject_id" value="<?= $selSub ?>">
        <table class="erp-table mb-3">
            <thead><tr><th>Roll No</th><th>Name</th><th>Internal (30)</th><th>External (70)</th><th>Practical</th></tr></thead>
            <tbody>
                <?php foreach($students as $st): ?>
                <tr>
                    <td><?= esc($st['roll_number']) ?></td><td><?= esc($st['name']) ?></td>
                    <td><input type="number" name="marks[<?= $st['id'] ?>][int]" class="form-control form-control-sm" value="<?= $st['internal_marks']??0 ?>" max="30"></td>
                    <td><input type="number" name="marks[<?= $st['id'] ?>][ext]" class="form-control form-control-sm" value="<?= $st['external_marks']??0 ?>" max="70"></td>
                    <td><input type="number" name="marks[<?= $st['id'] ?>][prac]" class="form-control form-control-sm" value="<?= $st['practical_marks']??0 ?>"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-erp-primary">Save Marks</button>
    </form>
</div></div>
<?php endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
