<?php
/**
 * Marksheet Generation – Admin / Student
 */
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(); 
$pageTitle = 'Marksheet';
$role = currentRole();
$students = [];
if ($role === 'admin' || $role === 'teacher') {
    $students = dbFetchAll("SELECT id, name, roll_number FROM students WHERE status='Active'");
    $selStudent = (int)($_GET['student'] ?? 0);
} else {
    $selStudent = $_SESSION['entity_id'];
}
$selExam = (int)($_GET['exam'] ?? 0);
$exams = dbFetchAll("SELECT id, exam_name FROM exams");
$marks = [];
$studentInfo = null;
if ($selStudent && $selExam) {
    $studentInfo = dbFetchOne("SELECT s.*, c.course_name, d.dept_name FROM students s JOIN courses c ON s.course_id=c.id JOIN departments d ON s.dept_id=d.id WHERE s.id=?", [$selStudent]);
    $marks = dbFetchAll("SELECT m.*, sub.sub_code, sub.sub_name, sub.credits FROM marks m JOIN subjects sub ON m.subject_id=sub.id WHERE m.student_id=? AND m.exam_id=?", [$selStudent, $selExam]);
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header no-print"><h1 class="page-title">Marksheet</h1><button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button></div>
<div class="erp-card mb-4 no-print"><div class="erp-card-body">
    <form method="GET" class="row g-3">
        <?php if($role !== 'student'): ?>
        <div class="col-md-5"><label class="erp-form-label">Student</label><select name="student" class="erp-form-select" required><option value="">Select Student</option><?php foreach($students as $st): ?><option value="<?= $st['id'] ?>" <?= $selStudent==$st['id']?'selected':'' ?>><?= esc($st['roll_number'].' - '.$st['name']) ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
        <div class="col-md-5"><label class="erp-form-label">Exam</label><select name="exam" class="erp-form-select" required><option value="">Select Exam</option><?php foreach($exams as $e): ?><option value="<?= $e['id'] ?>" <?= $selExam==$e['id']?'selected':'' ?>><?= esc($e['exam_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-erp-primary w-100">Generate</button></div>
    </form>
</div></div>
<?php if($marks && $studentInfo): ?>
<div class="marksheet-wrapper" id="marksheet-print">
    <div class="text-center mb-4">
        <h2 style="font-family:'Outfit';font-weight:700;color:var(--primary);">COLLEGE ERP UNIVERSITY</h2>
        <p>Statement of Marks / Grade Card</p>
    </div>
    <div class="row mb-4">
        <div class="col-8">
            <p class="mb-1"><strong>Name:</strong> <?= esc($studentInfo['name']) ?></p>
            <p class="mb-1"><strong>Roll No:</strong> <?= esc($studentInfo['roll_number']) ?></p>
            <p class="mb-1"><strong>Course:</strong> <?= esc($studentInfo['course_name']) ?> (Sem <?= esc($studentInfo['current_sem']) ?>)</p>
            <p class="mb-0"><strong>Department:</strong> <?= esc($studentInfo['dept_name']) ?></p>
        </div>
        <div class="col-4 text-end">
            <?php if($studentInfo['photo']): ?><img src="<?= esc(UPLOAD_URL.$studentInfo['photo']) ?>" style="width:100px;height:100px;object-fit:cover;border:1px solid #000;"><?php endif; ?>
        </div>
    </div>
    <table class="table table-bordered border-dark text-center align-middle">
        <thead><tr><th>Subject Code</th><th>Subject Name</th><th>Internal</th><th>External</th><th>Practical</th><th>Total</th><th>Grade</th><th>Credits</th></tr></thead>
        <tbody>
            <?php $totalMarks = 0; $maxMarks = 0; $totalCredits = 0; $totalGp = 0; foreach($marks as $m): 
                $tot = $m['internal_marks'] + $m['external_marks'] + $m['practical_marks'];
                $totalMarks += $tot; $maxMarks += 100;
                $totalCredits += $m['credits'];
                $totalGp += ($m['credits'] * $m['grade_points']);
            ?>
            <tr>
                <td><?= esc($m['sub_code']) ?></td><td class="text-start"><?= esc($m['sub_name']) ?></td>
                <td><?= (float)$m['internal_marks'] ?></td><td><?= (float)$m['external_marks'] ?></td><td><?= (float)$m['practical_marks'] ?></td>
                <td><strong><?= $tot ?></strong></td><td><strong><?= esc($m['grade']) ?></strong></td><td><?= esc($m['credits']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <?php $sgpa = $totalCredits > 0 ? round($totalGp / $totalCredits, 2) : 0; $pct = $maxMarks > 0 ? round(($totalMarks/$maxMarks)*100, 2) : 0; ?>
            <tr><th colspan="5" class="text-end">Grand Total</th><th><?= $totalMarks ?> / <?= $maxMarks ?></th><th>SGPA: <?= $sgpa ?></th><th><?= $totalCredits ?></th></tr>
        </tfoot>
    </table>
    <div class="mt-5 d-flex justify-content-between px-5">
        <div class="text-center"><br>______________________<br>Prepared By</div>
        <div class="text-center"><br>______________________<br>Controller of Examinations</div>
    </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
