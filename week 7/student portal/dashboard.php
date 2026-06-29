<?php
// dashboard.php - Secure Student Dashboard
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $s = $stmt->fetch();
    if (!$s) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
} catch (\PDOException $e) {
    die("An error occurred loading the dashboard.");
}

// Helpers
$initials   = strtoupper(substr($s['full_name'], 0, 1));
$nameParts  = explode(' ', $s['full_name']);
if (count($nameParts) > 1) { $initials = strtoupper($nameParts[0][0] . end($nameParts)[0]); }
$studentId  = 'MKU-' . str_pad($s['id'], 5, '0', STR_PAD_LEFT);
$dob        = !empty($s['date_of_birth'])   ? date('F j, Y', strtotime($s['date_of_birth']))   : '—';
$admDate    = !empty($s['admission_date'])  ? date('F j, Y', strtotime($s['admission_date']))  : '—';
$gpa        = number_format($s['gpa'], 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal – Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-wrapper">

    <!-- Top Navigation Bar -->
    <nav class="dashboard-nav">
        <h2>MKU Student Portal</h2>
        <a href="logout.php" class="btn logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Log Out</span>
        </a>
    </nav>

    <main class="dashboard-main">

        <!-- ══════════════════════════════════════════════════════
             SECTION 1 — PROFILE OVERVIEW HEADER
             ════════════════════════════════════════════════════ -->
        <div class="profile-header">
            <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="profile-header-info">
                <h3><?php echo htmlspecialchars($s['full_name']); ?></h3>
                <div class="profile-header-meta">
                    <!-- Student ID -->
                    <span class="student-id">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:4px;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                        <?php echo htmlspecialchars($studentId); ?>
                    </span>
                    <!-- Email -->
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <?php echo htmlspecialchars($s['email']); ?>
                    </span>
                    <!-- Status badge -->
                    <span class="badge badge-active">
                        <?php echo htmlspecialchars($s['account_status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════
             SECTIONS 2 & 3 — SIDE BY SIDE GRID
             ════════════════════════════════════════════════════ -->
        <div class="sections-grid">

            <!-- ── SECTION 2: ACADEMIC PROFILE ──────────────── -->
            <div class="section-card">
                <div class="section-card-title academic">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    Academic Profile
                </div>

                <!-- GPA prominent display -->
                <div class="gpa-display">
                    <div>
                        <div class="gpa-label">Current GPA</div>
                        <div class="gpa-scale">out of 4.00</div>
                    </div>
                    <div class="gpa-value"><?php echo htmlspecialchars($gpa); ?></div>
                </div>

                <div class="info-row">
                    <span class="info-label">Faculty / School</span>
                    <span class="info-value"><?php echo htmlspecialchars($s['faculty']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Major Course</span>
                    <span class="info-value"><?php echo htmlspecialchars($s['major']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current Year</span>
                    <span class="info-value">Year <?php echo htmlspecialchars($s['student_year']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current Semester</span>
                    <span class="info-value">Semester <?php echo htmlspecialchars($s['current_semester']); ?></span>
                </div>
            </div>

            <!-- ── SECTION 3: PERSONAL INFORMATION ──────────── -->
            <div class="section-card">
                <div class="section-card-title personal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Personal Information
                </div>

                <div class="info-row">
                    <span class="info-label">Date of Birth</span>
                    <span class="info-value"><?php echo htmlspecialchars($dob); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Gender</span>
                    <span class="info-value"><?php echo htmlspecialchars($s['gender']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($s['phone_number']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Physical Address</span>
                    <span class="info-value"><?php echo nl2br(htmlspecialchars($s['physical_address'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Admission Date</span>
                    <span class="info-value"><?php echo htmlspecialchars($admDate); ?></span>
                </div>
            </div>

        </div><!-- /.sections-grid -->
    </main>
</div>
</body>
</html>
