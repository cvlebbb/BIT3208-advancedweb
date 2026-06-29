<?php
// signup.php - Secure Student Registration Page
session_start();

if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error        = '';
$success      = false;
$generatedEmail = '';

// Form field values (retained on error)
$fullName       = '';
$studentYear    = '';
$semester       = '';
$major          = '';
$faculty        = '';
$gpa            = '';
$dob            = '';
$gender         = '';
$phone          = '';
$address        = '';
$admissionDate  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName      = trim($_POST['full_name']      ?? '');
    $studentYear   = intval($_POST['student_year']  ?? 0);
    $semester      = intval($_POST['semester']       ?? 0);
    $major         = trim($_POST['major']            ?? '');
    $faculty       = trim($_POST['faculty']          ?? '');
    $gpa           = trim($_POST['gpa']              ?? '');
    $dob           = trim($_POST['date_of_birth']    ?? '');
    $gender        = trim($_POST['gender']           ?? '');
    $phone         = trim($_POST['phone_number']     ?? '');
    $address       = trim($_POST['physical_address'] ?? '');
    $admissionDate = trim($_POST['admission_date']   ?? '');
    $password      = $_POST['password']         ?? '';
    $confirmPass   = $_POST['confirm_password'] ?? '';

    // ── Validation ───────────────────────────────────────
    if (empty($fullName) || empty($major) || empty($faculty) || empty($phone) ||
        empty($address) || empty($dob) || empty($admissionDate) ||
        empty($gender) || empty($password) || empty($confirmPass)) {
        $error = "All fields are required.";
    } elseif ($studentYear < 1 || $studentYear > 4) {
        $error = "Student year must be between 1 and 4.";
    } elseif ($semester < 1 || $semester > 2) {
        $error = "Semester must be 1 or 2.";
    } elseif (!is_numeric($gpa) || $gpa < 0 || $gpa > 4) {
        $error = "GPA must be a number between 0.00 and 4.00.";
    } elseif (preg_match('/[^a-zA-Z\s]/', $fullName)) {
        $error = "Full Name should only contain letters and spaces.";
    } elseif ($password !== $confirmPass) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        require_once 'db.php';
        try {
            // ── Generate unique student email ─────────────
            $cleaned   = preg_replace('/[^a-zA-Z\s]/', '', $fullName);
            $parts     = array_values(array_filter(explode(' ', strtolower(trim($cleaned)))));
            $baseEmail = count($parts) === 1 ? $parts[0] : $parts[0] . '.' . end($parts);
            $domain    = '@student.mku.ac.ke';
            $candidate = $baseEmail . $domain;

            $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
            $check->execute([$candidate]);
            $count = $check->fetchColumn();
            if ($count > 0) {
                $n = 1;
                do {
                    $candidate = $baseEmail . $n . $domain;
                    $check->execute([$candidate]);
                    $count = $check->fetchColumn();
                    $n++;
                } while ($count > 0);
            }
            $generatedEmail = $candidate;

            // ── Insert student ────────────────────────────
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare("INSERT INTO students
                (email, password_hash, full_name, student_year, major,
                 faculty, current_semester, gpa, account_status,
                 date_of_birth, gender, phone_number, physical_address, admission_date)
                VALUES (?,?,?,?,?,?,?,?,'Active',?,?,?,?,?)");
            $ins->execute([
                $generatedEmail, $hash, $fullName, $studentYear, $major,
                $faculty, $semester, floatval($gpa),
                $dob, $gender, $phone, $address, $admissionDate
            ]);
            $success = true;
        } catch (\PDOException $e) {
            $error = "An error occurred during registration. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal – Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="split-container">
    <!-- Hero Panel -->
    <div class="split-hero">
        <div class="hero-content">
            <h1>Student Portal</h1>
            <p>Register your MKU student account. Fill in your academic and personal details — your university email will be auto-generated from your name instantly.</p>
        </div>
    </div>

    <!-- Form Panel -->
    <div class="split-form">
        <div class="form-wrapper">
            <?php if ($success): ?>
                <h2>Registration Successful!</h2>
                <p class="subtitle">Your student account is ready. Save your generated email.</p>

                <div class="alert alert-success" role="alert" style="flex-direction:column;align-items:flex-start;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <strong>Your Generated Student Email:</strong>
                    </div>
                    <div style="font-size:16px;font-weight:700;font-family:monospace;background:rgba(0,0,0,.25);padding:10px 14px;border-radius:8px;width:100%;margin-top:4px;">
                        <?php echo htmlspecialchars($generatedEmail); ?>
                    </div>
                </div>

                <p style="color:var(--text-secondary);font-size:14px;margin-bottom:22px;">
                    Use this email and your chosen password to log in.
                </p>
                <a href="login.php" class="btn">
                    <span>Go to Secure Login</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </a>

            <?php else: ?>
                <h2>Student Sign Up</h2>
                <p class="subtitle">Complete all sections below.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="signup.php" method="POST" autocomplete="off">

                    <!-- Section 1: Personal Information -->
                    <div class="form-section-title">Personal Information</div>

                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control"
                               placeholder="Full Names" required
                               value="<?php echo htmlspecialchars($fullName); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control"
                                   required value="<?php echo htmlspecialchars($dob); ?>">
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select name="gender" id="gender" class="form-control" required>
                                <option value="" disabled <?php echo empty($gender) ? 'selected' : ''; ?>>Select Gender</option>
                                <option value="Male"   <?php echo $gender === 'Male'   ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other"  <?php echo $gender === 'Other'  ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="tel" name="phone_number" id="phone_number" class="form-control"
                                   placeholder="+254 7XX XXX XXX" required
                                   value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                        <div class="form-group">
                            <label for="admission_date">Admission Date</label>
                            <input type="date" name="admission_date" id="admission_date" class="form-control"
                                   required value="<?php echo htmlspecialchars($admissionDate); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="physical_address">Physical Address</label>
                        <textarea name="physical_address" id="physical_address" class="form-control"
                                  placeholder="Street, City" required><?php echo htmlspecialchars($address); ?></textarea>
                    </div>

                    <!-- Section 2: Academic Details -->
                    <div class="form-section-title">Academic Details</div>

                    <div class="form-group">
                        <label for="faculty">Faculty / School</label>
                        <input type="text" name="faculty" id="faculty" class="form-control"
                               placeholder="e.g. School of Computing & Informatics" required
                               value="<?php echo htmlspecialchars($faculty); ?>">
                    </div>

                    <div class="form-group">
                        <label for="major">Major / Course</label>
                        <input type="text" name="major" id="major" class="form-control"
                               placeholder="e.g. Computer Science" required
                               value="<?php echo htmlspecialchars($major); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="student_year">Current Year</label>
                            <select name="student_year" id="student_year" class="form-control" required>
                                <option value="" disabled <?php echo empty($studentYear) ? 'selected' : ''; ?>>Select Year</option>
                                <?php for ($y = 1; $y <= 4; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $studentYear === $y ? 'selected' : ''; ?>>Year <?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="semester">Current Semester</label>
                            <select name="semester" id="semester" class="form-control" required>
                                <option value="" disabled <?php echo empty($semester) ? 'selected' : ''; ?>>Select Semester</option>
                                <option value="1" <?php echo $semester === 1 ? 'selected' : ''; ?>>Semester 1</option>
                                <option value="2" <?php echo $semester === 2 ? 'selected' : ''; ?>>Semester 2</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gpa">Current GPA (0.00 – 4.00)</label>
                        <input type="number" name="gpa" id="gpa" class="form-control"
                               placeholder="e.g. 3.75" min="0" max="4" step="0.01" required
                               value="<?php echo htmlspecialchars($gpa); ?>">
                    </div>

                    <!-- Section 3: Account Security -->
                    <div class="form-section-title">Account Security</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn">
                        <span>Create Student Account</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </button>
                </form>

                <div class="card-footer">
                    Already have an account? <a href="login.php">Log In</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
