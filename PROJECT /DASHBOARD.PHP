<?php
/**
 * Student Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('student');
$pageTitle = 'Student Dashboard';
$student = $_SESSION['entity_id'];
$att = dbFetchOne("SELECT ROUND(100*SUM(status='Present')/COUNT(*),1) pct FROM attendance WHERE student_id=?", [$student]);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1 class="page-title">Welcome, <?= esc($_SESSION['user_name']) ?>!</h1></div>
<div class="row g-3">
    <div class="col-md-3"><div class="stat-card emerald"><div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div><div class="stat-info"><div class="stat-value"><?= $att['pct']??0 ?>%</div><div class="stat-label">Overall Attendance</div></div></div></div>
</div>
<div class="erp-card mt-4">
    <div class="erp-card-header"><h2 class="card-title">Quick Links</h2></div>
    <div class="erp-card-body d-flex gap-3">
        <a href="<?= APP_URL ?>/modules/marks/marksheet.php" class="btn btn-erp-primary">View Marksheet</a>
        <a href="<?= APP_URL ?>/modules/attendance/report.php?view=student&student=<?= $student ?>" class="btn btn-outline-primary">View Detailed Attendance</a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
/**
 * Teacher Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin('teacher');
$pageTitle = 'Teacher Dashboard';
$teacher = $_SESSION['entity_id'];
$subs = dbFetchOne("SELECT COUNT(*) c FROM subjects WHERE teacher_id=?", [$teacher])['c'];
$classes = dbFetchAll("SELECT sub_name, sub_code FROM subjects WHERE teacher_id=?", [$teacher]);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1 class="page-title">Welcome, <?= esc($_SESSION['user_name']) ?>!</h1></div>
<div class="row g-3">
    <div class="col-md-3"><div class="stat-card indigo"><div class="stat-icon"><i class="bi bi-book-fill"></i></div><div class="stat-info"><div class="stat-value"><?= $subs ?></div><div class="stat-label">Assigned Subjects</div></div></div></div>
</div>
<div class="erp-card mt-4">
    <div class="erp-card-header"><h2 class="card-title">My Classes</h2></div>
    <div class="erp-card-body">
        <ul><?php foreach($classes as $c): ?><li><?= esc($c['sub_code'].' - '.$c['sub_name']) ?></li><?php endforeach; ?></ul>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
